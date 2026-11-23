<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Event;
use App\Models\VotePurchase;
use App\Models\Voter;
use App\Support\Plans;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

/**
 * Demo ballots: ~35% of each started event's voters vote, with a weighted
 * random spread so the results board looks realistic. Secret ballot: only the
 * candidate totals change; nothing links a vote to a voter.
 */
class VoteSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::with('categories.candidates')->where('voting_mode', Event::MODE_CODES)->get();

        foreach ($events as $event) {
            // Nobody can have voted in an event that has not opened yet.
            if ($event->status === Event::STATUS_UPCOMING || $event->categories->isEmpty()) {
                continue;
            }

            $voters = Voter::where('event_id', $event->id)->orderBy('id')->get();
            $turnout = $voters->take((int) round($voters->count() * 0.35));
            $totals = [];
            $ballots = [];
            $rankedRows = [];

            foreach ($turnout as $voter) {
                foreach ($event->categories as $category) {
                    $candidates = $category->candidates->values();
                    // Only categories open to this voter's groups.
                    if ($candidates->isEmpty() || ! $category->allows($voter->groups)) {
                        continue;
                    }
                    $ballots[$category->id] = ($ballots[$category->id] ?? 0) + 1;
                    if ($category->isRanked()) {
                        // A first choice weighted like the others, then a random order for the rest.
                        $first = $this->weightedPick($candidates->all());
                        $rest = $candidates->reject(fn ($c) => $c->id === $first->id)->shuffle()->take(random_int(0, $candidates->count() - 1));
                        $ranking = collect([$first])->concat($rest)->pluck('id')->all();
                        $rankedRows[] = ['id' => (string) \Illuminate\Support\Str::uuid(), 'category_id' => $category->id, 'ranking' => json_encode($ranking)];
                        $totals[$first->id] = ($totals[$first->id] ?? 0) + 1;
                        continue;
                    }
                    $limit = $category->max_choices ?? $candidates->count();
                    $picks = $limit > 1
                        ? $candidates->random(min($limit, $candidates->count()))
                        : collect([$this->weightedPick($candidates->all())]);

                    foreach ($picks as $candidate) {
                        $totals[$candidate->id] = ($totals[$candidate->id] ?? 0) + 1;
                    }
                }
            }

            foreach ($totals as $candidateId => $votes) {
                Candidate::whereKey($candidateId)->increment('votes', $votes);
            }
            foreach ($ballots as $categoryId => $n) {
                \App\Models\Category::whereKey($categoryId)->increment('ballots', $n);
            }
            foreach (array_chunk($rankedRows, 500) as $chunk) {
                \App\Models\RankedBallot::insert($chunk);
            }
            $from = $event->starts_at ?? now()->subHours(6);
            $to = min(now(), $event->ends_at ?? now());
            $span = max(60, $to->getTimestamp() - $from->getTimestamp());
            foreach ($turnout as $i => $voter) {
                // Busier early on, tailing off: a realistic turnout curve.
                $fraction = 1 - sqrt(1 - ($i + random_int(0, 100) / 100) / max(1, $turnout->count()));
                $voter->forceFill(['used_at' => $from->copy()->addSeconds((int) ($span * min(1, max(0, $fraction))))])->save();
            }
        }

        $this->seedPaidVotes();

        $this->command?->info('Seeded sample votes (35% turnout) for events that have started.');
    }

    /** Demo purchases for paid-voting events (no real money: references start with DEMO). */
    private function seedPaidVotes(): void
    {
        $events = Event::with('candidates')->where('voting_mode', Event::MODE_PAID)->get();
        foreach ($events as $event) {
            if ($event->status === Event::STATUS_UPCOMING || $event->candidates->isEmpty()) {
                continue;
            }
            $from = $event->starts_at ?? now()->subDay();
            $span = max(60, now()->getTimestamp() - $from->getTimestamp());
            $candidates = $event->candidates->values();
            for ($i = 0; $i < 60; $i++) {
                $candidate = $this->weightedPick($candidates->all());
                $qty = [1, 2, 2, 4, 5, 10, 20][random_int(0, 6)];
                $amount = $qty * (int) $event->vote_price_naira * 100;
                VotePurchase::create([
                    'event_id' => $event->id,
                    'candidate_id' => $candidate->id,
                    'candidate_name' => $candidate->name,
                    'quantity' => $qty,
                    'amount_kobo' => $amount,
                    'fee_kobo' => Plans::voteFeeKobo($amount),
                    'reference' => 'DEMO-' . Str::upper(Str::random(12)),
                    'email' => "fan{$i}@example.com",
                    'status' => VotePurchase::SUCCESS,
                    'channel' => ['card', 'bank_transfer', 'ussd'][random_int(0, 2)],
                    'paid_at' => $from->copy()->addSeconds(random_int(0, $span)),
                ]);
                Candidate::whereKey($candidate->id)->increment('votes', $qty);
            }
        }
    }

    /** Earlier candidates get more weight so each category has a clear-ish leader. */
    private function weightedPick(array $candidates): mixed
    {
        $weights = array_map(fn ($i) => count($candidates) - $i + 1, array_keys($candidates));
        $roll = random_int(1, array_sum($weights));
        foreach ($candidates as $i => $candidate) {
            $roll -= $weights[$i];
            if ($roll <= 0) {
                return $candidate;
            }
        }
        return end($candidates);
    }
}
