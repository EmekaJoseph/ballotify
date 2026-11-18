<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\VoteCast;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function store(Request $request, string $token)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'choices' => ['required', 'array', 'min:1', 'max:500'],
            'choices.*.category_id' => ['required', 'integer'],
            'choices.*.candidate_id' => ['required', 'integer'],
        ]);

        $event = Event::with('type')->where('link_token', $token)->firstOrFail();

        if (! $event->isOpen()) {
            return response()->json([
                'message' => $event->status === Event::STATUS_UPCOMING
                    ? 'This election has not started yet.'
                    : 'This election has ended.',
            ], 403);
        }

        // De-duplicate identical (category, candidate) pairs.
        $cleanChoices = collect($data['choices'])
            ->map(fn ($c) => ['category_id' => (int) $c['category_id'], 'candidate_id' => (int) $c['candidate_id']])
            ->unique(fn ($c) => $c['category_id'] . ':' . $c['candidate_id'])
            ->values()
            ->all();

        $categories = Category::whereIn('id', array_column($cleanChoices, 'category_id'))
            ->where('event_id', $event->id)->get()->keyBy('id');
        $candidates = Candidate::whereIn('id', array_column($cleanChoices, 'candidate_id'))
            ->where('event_id', $event->id)->get()->keyBy('id');

        foreach ($cleanChoices as $choice) {
            if (! isset($categories[$choice['category_id']])) {
                return response()->json(['message' => 'Invalid category'], 422);
            }
            if (! isset($candidates[$choice['candidate_id']])) {
                return response()->json(['message' => 'Invalid candidate'], 422);
            }
            if ($candidates[$choice['candidate_id']]->category_id !== $choice['category_id']) {
                return response()->json(['message' => 'Candidate not in category'], 422);
            }
        }

        if ($event->selection_mode === 'single') {
            $perCategory = array_count_values(array_column($cleanChoices, 'category_id'));
            if (max($perCategory) > 1) {
                return response()->json(['message' => 'Only one candidate per category is allowed'], 422);
            }
        }

        $candidateIds = array_values(array_unique(array_column($cleanChoices, 'candidate_id')));

        $accepted = DB::transaction(function () use ($event, $data, $candidateIds) {
            // The row lock must be taken inside the transaction, otherwise two
            // concurrent requests with the same code could both cast a ballot.
            $voter = Voter::where('event_id', $event->id)
                ->where('code', VoterController::normalizeCode($data['code']))
                ->lockForUpdate()
                ->first();

            if (! $voter || $voter->used_at !== null) {
                return false;
            }

            // SECRET BALLOT: only the running totals change. No row records which
            // voter chose what, when, or from where, so a ballot can never be
            // traced back to a code, not even by the organiser or a DBA.
            Candidate::whereIn('id', $candidateIds)->where('event_id', $event->id)->increment('votes');

            $voter->used_at = now();
            $voter->save();

            return true;
        });

        if (! $accepted) {
            return response()->json(['message' => 'Invalid or used code'], 422);
        }

        // Live results ping (carries no ballot content). Skipped when results aren't public.
        if ($event->resultsArePublic()) {
            try {
                event(new VoteCast($event->id));
            } catch (\Throwable $e) {
                // A broadcasting outage must never make a recorded vote look failed.
                report($e);
            }
        }

        return response()->json(['message' => 'Vote recorded']);
    }
}
