<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * Who won a category: most votes wins, and a tie for first place shares the win.
 * The single place that decides winners, used by the results pages, the report
 * and certificates.
 */
class Tally
{
    /**
     * @param  Collection  $candidates  the category's candidates (id, votes)
     * @return array{winners: int[], order: int[]}
     */
    public static function category(Category $category, Collection $candidates): array
    {
        $sorted = $candidates->sortByDesc('votes')->values();
        $top = (int) ($sorted->first()->votes ?? 0);

        return [
            'winners' => $top > 0 ? $sorted->where('votes', $top)->pluck('id')->map(fn ($id) => (int) $id)->values()->all() : [],
            'order' => $sorted->pluck('id')->all(),
        ];
    }
}
