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

/** Casting a secret ballot with a one-time code. */
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
        $picks = []; // category id => candidate ids
        foreach ($clean as $choice) {
            $picks[$choice['category_id']][] = $choice['candidate_id'];
        }
        foreach ($picks as $categoryId => $ids) {
            $limit = $categories[$categoryId]->max_choices;
            if ($limit && count($ids) > $limit) {
                throw new BallotRejected($limit === 1
                    ? __('Choose only one candidate in “:category”.', ['category' => $categories[$categoryId]->name])
                    : __('You can pick at most :limit in “:category”.', ['limit' => $limit, 'category' => $categories[$categoryId]->name]), 422);
            }
        }

        $receipt = DB::transaction(function () use ($event, $code, $picks) {
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
            foreach ($picks as $categoryId => $candidateIds) {
                Candidate::whereIn('id', $candidateIds)->where('event_id', $event->id)->increment('votes');
                Category::where('id', $categoryId)->increment('ballots');
            }

            $voter->update(['used_at' => now()]);

            // Receipt: proves this ballot was counted, without saying who cast it or how.
            return BallotReceipt::issue($event->id);
        });

        if ($receipt === null) {
            throw new BallotRejected(__('Invalid or used code'), 422, unknownCode: true);
        }

        // Live results ping (carries no ballot content): always to the organisers' private
        // channel, and to the public one only while results are public.
        try {
            event(new VoteCast($event->id, $event->resultsArePublic()));
        } catch (\Throwable $e) {
            report($e); // a broadcasting outage must never make a recorded vote look failed
        }

        return $receipt;
    }
}
