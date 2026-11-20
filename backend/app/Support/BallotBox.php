<?php

namespace App\Support;

use App\Events\VoteCast;
use App\Http\Controllers\Api\VoterController;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Voter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Casting a secret ballot. Shared by the web ballot and USSD voting, so the
 * rules can never differ between them.
 */
class BallotBox
{
    /**
     * @param  array<int, array{category_id: int, candidate_id: int}>  $choices
     * @return string the ballot receipt
     *
     * @throws BallotRejected with a voter-friendly message and an HTTP status
     */
    public static function cast(Event $event, string $code, array $choices): string
    {
        if ($event->isPaid()) {
            throw new BallotRejected(__('This event uses paid voting. Choose a candidate and pay to vote.'), 422);
        }
        if (! $event->isOpen()) {
            throw new BallotRejected($event->status === Event::STATUS_UPCOMING
                ? __('This election has not started yet.')
                : __('This election has ended.'), 403);
        }

        // De-duplicate identical (category, candidate) pairs.
        $clean = collect($choices)
            ->map(fn ($c) => ['category_id' => (int) $c['category_id'], 'candidate_id' => (int) $c['candidate_id']])
            ->unique(fn ($c) => $c['category_id'] . ':' . $c['candidate_id'])
            ->values()
            ->all();
        if (! $clean) {
            throw new BallotRejected(__('Choose at least one candidate.'), 422);
        }

        $categories = Category::whereIn('id', array_column($clean, 'category_id'))
            ->where('event_id', $event->id)->get()->keyBy('id');
        $candidates = Candidate::whereIn('id', array_column($clean, 'candidate_id'))
            ->where('event_id', $event->id)->get()->keyBy('id');

        foreach ($clean as $choice) {
            if (! isset($categories[$choice['category_id']])) {
                throw new BallotRejected('Invalid category', 422);
            }
            if (! isset($candidates[$choice['candidate_id']])) {
                throw new BallotRejected('Invalid candidate', 422);
            }
            if ($candidates[$choice['candidate_id']]->category_id !== $choice['category_id']) {
                throw new BallotRejected('Candidate not in category', 422);
            }
        }

        // Each category has its own pick limit: 1 = choose one, null = no limit.
        foreach (array_count_values(array_column($clean, 'category_id')) as $categoryId => $count) {
            $limit = $categories[$categoryId]->max_choices;
            if ($limit && $count > $limit) {
                throw new BallotRejected($limit === 1
                    ? __('Choose only one candidate in “:category”.', ['category' => $categories[$categoryId]->name])
                    : __('You can pick at most :limit in “:category”.', ['limit' => $limit, 'category' => $categories[$categoryId]->name]), 422);
            }
        }

        $candidateIds = array_values(array_unique(array_column($clean, 'candidate_id')));

        $receipt = DB::transaction(function () use ($event, $code, $candidateIds) {
            // The row lock must be taken inside the transaction, otherwise two
            // concurrent requests with the same code could both cast a ballot.
            $voter = Voter::where('event_id', $event->id)
                ->where('code', VoterController::normalizeCode($code))
                ->lockForUpdate()
                ->first();

            if (! $voter || $voter->used_at !== null) {
                return null;
            }

            // SECRET BALLOT: only the running totals change. No row records which
            // voter chose what, so a ballot can never be traced back to a code,
            // not even by the organiser or a DBA.
            Candidate::whereIn('id', $candidateIds)->where('event_id', $event->id)->increment('votes');

            $voter->used_at = now();
            $voter->save();

            // Receipt: proves this ballot was counted, without saying who cast it or how.
            return BallotReceipt::issue($event->id);
        });

        if ($receipt === null) {
            throw new BallotRejected(__('Invalid or used code'), 422);
        }

        // Live results ping (carries no ballot content). Skipped when results aren't public.
        if ($event->resultsArePublic()) {
            try {
                event(new VoteCast($event->id));
            } catch (\Throwable $e) {
                report($e); // a broadcasting outage must never make a recorded vote look failed
            }
        }

        return $receipt;
    }
}
