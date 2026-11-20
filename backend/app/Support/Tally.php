<?php

namespace App\Support;

use App\Models\Category;
use App\Models\RankedBallot;
use Illuminate\Support\Collection;

/**
 * Who won a category, by its voting method. The single place that decides
 * winners, used by the results pages, the report and certificates.
 *
 *   plurality (choose one / pick up to N / approval): most votes wins, ties share.
 *   ranked: instant-runoff over the stored rankings (see RankedChoice).
 */
class Tally
{
    /**
     * @param  Collection  $candidates  the category's candidates (id, votes)
     * @return array{winners: int[], order: int[], rounds: ?array}
     */
    public static function category(Category $category, Collection $candidates): array
    {
        if ($category->isRanked()) {
            $ids = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
            $rankings = RankedBallot::where('category_id', $category->id)->pluck('ranking')->all();
            $count = RankedChoice::count($ids, $rankings);
            // Candidates without a place (no candidates at all in the count) keep their first-choice order.
            $order = array_values(array_unique(array_merge($count['order'], $candidates->sortByDesc('votes')->pluck('id')->all())));

            return [
                'winners' => $count['winners'],
                'order' => $order,
                'rounds' => array_map(fn ($r) => [
                    'counts' => collect($r['counts'])->map(fn ($votes, $id) => ['id' => (int) $id, 'votes' => $votes])->values()->all(),
                    'eliminated' => $r['eliminated'],
                    'exhausted' => $r['exhausted'],
                    'continuing' => $r['continuing'],
                ], $count['rounds']),
            ];
        }

        $sorted = $candidates->sortByDesc('votes')->values();
        $top = (int) ($sorted->first()->votes ?? 0);

        return [
            'winners' => $top > 0 ? $sorted->where('votes', $top)->pluck('id')->map(fn ($id) => (int) $id)->values()->all() : [],
            'order' => $sorted->pluck('id')->all(),
            'rounds' => null,
        ];
    }
}
