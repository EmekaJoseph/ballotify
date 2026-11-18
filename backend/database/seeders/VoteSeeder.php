<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Event;
use App\Models\Voter;
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
        $events = Event::with(['type', 'categories.candidates'])->get();

        foreach ($events as $event) {
            // Nobody can have voted in an event that has not opened yet.
            if ($event->status === Event::STATUS_UPCOMING || $event->categories->isEmpty()) {
                continue;
            }

            $voters = Voter::where('event_id', $event->id)->orderBy('id')->get();
            $turnout = $voters->take((int) round($voters->count() * 0.35));
            $totals = [];

            foreach ($turnout as $voter) {
                foreach ($event->categories as $category) {
                    $candidates = $category->candidates->values();
                    if ($candidates->isEmpty()) {
                        continue;
                    }
                    $picks = $event->selection_mode === 'multiple'
                        ? $candidates->random(min(2, $candidates->count()))
                        : collect([$this->weightedPick($candidates->all())]);

                    foreach ($picks as $candidate) {
                        $totals[$candidate->id] = ($totals[$candidate->id] ?? 0) + 1;
                    }
                }
            }

            foreach ($totals as $candidateId => $votes) {
                Candidate::whereKey($candidateId)->increment('votes', $votes);
            }
            Voter::whereIn('id', $turnout->pluck('id'))->update(['used_at' => now()]);
        }

        $this->command?->info('Seeded sample votes (35% turnout) for events that have started.');
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
