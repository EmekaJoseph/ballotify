<?php

namespace App\Support;

/**
 * Instant-runoff counting for ranked-choice categories.
 *
 * Every round, each ballot counts for its highest-ranked candidate still in
 * the race. A candidate with more than half of those votes wins. Otherwise
 * the last-placed candidate is eliminated and their ballots move to the next
 * choice. Ballots with no remaining choices are "exhausted" and stop counting.
 *
 * Ties for last place are broken by the most recent earlier round in which
 * those candidates differed; if they never differed, all of them go out
 * together. If everyone left is tied, they share the win.
 */
class RankedChoice
{
    /**
     * @param  int[]  $candidateIds
     * @param  iterable<int[]>  $rankings  candidate ids, first choice first
     * @return array{winners: int[], rounds: array<int, array{counts: array<int, int>, eliminated: int[], exhausted: int, continuing: int}>, order: int[]}
     */
    public static function count(array $candidateIds, iterable $rankings): array
    {
        $rankings = is_array($rankings) ? $rankings : iterator_to_array($rankings, false);
        $active = array_values(array_unique(array_map('intval', $candidateIds)));
        $rounds = [];
        $eliminatedOrder = [];
        $winners = [];

        while ($active) {
            $counts = array_fill_keys($active, 0);
            $exhausted = 0;
            foreach ($rankings as $ranking) {
                $choice = null;
                foreach ($ranking as $id) {
                    if (isset($counts[(int) $id])) {
                        $choice = (int) $id;
                        break;
                    }
                }
                $choice === null ? $exhausted++ : $counts[$choice]++;
            }
            $continuing = array_sum($counts);
            arsort($counts);
            $round = ['counts' => $counts, 'eliminated' => [], 'exhausted' => $exhausted, 'continuing' => $continuing];

            if ($continuing === 0) {
                $rounds[] = $round; // no votes at all: nobody wins
                break;
            }
            $top = max($counts);
            if ($top * 2 > $continuing || count($active) === 1) {
                $winners = array_keys(array_filter($counts, fn ($n) => $n === $top));
                $rounds[] = $round;
                break;
            }

            $low = min($counts);
            $lowest = array_keys(array_filter($counts, fn ($n) => $n === $low));
            if (count($lowest) === count($active)) {
                $winners = $lowest; // everyone left is tied
                $rounds[] = $round;
                break;
            }
            $lowest = self::breakTie($lowest, $rounds);

            $round['eliminated'] = $lowest;
            $rounds[] = $round;
            $eliminatedOrder[] = $lowest;
            $active = array_values(array_diff($active, $lowest));
        }

        // Finishing order: winners, then the rest of the last round, then the eliminated, latest first.
        $order = $winners;
        $last = end($rounds) ?: ['counts' => []];
        foreach (array_keys($last['counts']) as $id) {
            if (! in_array($id, $order, true)) {
                $order[] = $id;
            }
        }
        foreach (array_reverse($eliminatedOrder) as $group) {
            foreach ($group as $id) {
                if (! in_array($id, $order, true)) {
                    $order[] = $id;
                }
            }
        }

        return ['winners' => $winners, 'rounds' => $rounds, 'order' => $order];
    }

    /** Of the candidates tied for last, keep only those who were also lowest most recently. */
    private static function breakTie(array $tied, array $rounds): array
    {
        for ($i = count($rounds) - 1; $i >= 0 && count($tied) > 1; $i--) {
            $counts = $rounds[$i]['counts'];
            $scores = array_map(fn ($id) => $counts[$id] ?? 0, $tied);
            $min = min($scores);
            if ($min !== max($scores)) {
                $tied = array_values(array_filter($tied, fn ($id) => ($counts[$id] ?? 0) === $min));
            }
        }

        return $tied;
    }
}
