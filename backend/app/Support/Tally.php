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
        if ($category->isMotion()) {
            $by = fn ($choice) => (int) ($candidates->firstWhere('motion_choice', $choice)->votes ?? 0);
            [$yes, $no, $abstain] = [$by('yes'), $by('no'), $by('abstain')];
            $base = $yes + $no; // abstentions don't count towards the majority
            $rule = $category->pass_rule ?: 'simple';
            $passed = match ($rule) {
                'two_thirds' => $base > 0 && $yes * 3 >= $base * 2,
                'three_quarters' => $base > 0 && $yes * 4 >= $base * 3,
                default => $yes > $no,
            };
            $order = $candidates->sortBy(fn ($c) => array_search($c->motion_choice, ['yes', 'no', 'abstain'], true))->pluck('id')->all();

            return [
                'winners' => [],
                'order' => $order,
                'rounds' => null,
                'motion' => ['yes' => $yes, 'no' => $no, 'abstain' => $abstain, 'rule' => $rule, 'decided' => $base > 0, 'passed' => $passed],
            ];
        }
        if ($category->isRanked()) {
            $ids = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
            $ballots = RankedBallot::where('category_id', $category->id)->get(['ranking', 'weight']);
            $count = RankedChoice::count($ids, $ballots->pluck('ranking')->all(), $ballots->pluck('weight')->all());
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
