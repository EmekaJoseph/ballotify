<?php

namespace App\Support;

use App\Events\VoteCast;
use App\Http\Controllers\Api\VoterController;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\RankedBallot;
use App\Models\Voter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Casting a secret ballot. Shared by the web ballot and USSD voting, so the
 * rules can never differ between them.
 */
class BallotBox
{
    /**
     * @param  array<int, array{category_id: int, candidate_id: int, rank?: int}>  $choices
     *         For ranked-choice categories, `rank` (1 = first choice) orders the picks;
     *         without it, the order they are listed in is used.
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

        // De-duplicate identical (category, candidate) pairs; keep the voter's ranking order.
        $clean = collect($choices)
            ->values()
            ->map(fn ($c, $i) => [
                'category_id' => (int) $c['category_id'],
                'candidate_id' => (int) $c['candidate_id'],
                'rank' => isset($c['rank']) && is_numeric($c['rank']) ? (int) $c['rank'] : 1000 + $i,
            ])
            ->sortBy('rank')
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

        // Plurality categories add to every pick's total. Ranked categories add only to the
        // first choice's total (the "first preferences"), and keep the ranking for the rounds.
        $picks = [];     // category id => candidate ids that gain votes
        $rankings = [];  // ranked category id => full ranking
        foreach ($clean as $choice) {
            if ($categories[$choice['category_id']]->isRanked()) {
                $rankings[$choice['category_id']][] = $choice['candidate_id'];
            } else {
                $picks[$choice['category_id']][] = $choice['candidate_id'];
            }
        }
        foreach ($rankings as $categoryId => $ranking) {
            $picks[$categoryId] = [$ranking[0]];
        }

        $receipt = DB::transaction(function () use ($event, $code, $picks, $rankings, $categories) {
            // The row lock must be taken inside the transaction, otherwise two
            // concurrent requests with the same code could both cast a ballot.
            $voter = Voter::where('event_id', $event->id)
                ->where('code', VoterController::normalizeCode($code))
                ->lockForUpdate()
                ->first();

            if (! $voter || $voter->used_at !== null) {
                return null;
            }
            // Proxy: someone else votes for this member, so they can't vote twice.
            if ($voter->proxy_voter_id !== null) {
                $holder = Voter::where('id', $voter->proxy_voter_id)->value('name');
                throw new BallotRejected($holder
                    ? __('Your vote is being cast by your proxy, :name. Ask the organiser if this is wrong.', ['name' => $holder])
                    : __('Your vote is being cast by your proxy. Ask the organiser if this is wrong.'), 422);
            }
            // The holder, plus the members who gave them their proxy (locked, so a vote and
            // a proxy change can't cross). Weights count only when weighted votes are on.
            $members = MeetingRules::members($event, $voter, lock: true);

            // Voter groups: only categories at least one of these members may vote in, and
            // each category counts only the members allowed in it.
            $weights = [];
            foreach (array_keys($picks) as $categoryId) {
                $weights[$categoryId] = MeetingRules::categoryWeight($event, $members, $categories[$categoryId]);
                if ($weights[$categoryId] < 1) {
                    throw new BallotRejected(__('You can’t vote in “:category”.', ['category' => $categories[$categoryId]->name]), 422);
                }
            }

            // SECRET BALLOT: only the running totals change. No row records which
            // voter chose what, so a ballot can never be traced back to a code,
            // not even by the organiser or a DBA.
            foreach ($picks as $categoryId => $candidateIds) {
                Candidate::whereIn('id', $candidateIds)->where('event_id', $event->id)->increment('votes', $weights[$categoryId]);
                Category::where('id', $categoryId)->increment('ballots', $weights[$categoryId]);
            }
            if ($rankings) {
                // No voter id and no time, and random ids (see RankedBallot).
                RankedBallot::insert(array_map(fn ($ranking, $categoryId) => [
                    'id' => (string) Str::uuid(),
                    'category_id' => $categoryId,
                    'ranking' => json_encode(array_values($ranking)),
                    'weight' => $weights[$categoryId],
                ], $rankings, array_keys($rankings)));
            }

            // Everyone this ballot spoke for has now voted.
            Voter::whereIn('id', $members->pluck('id'))->update(['used_at' => now()]);

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
