<?php

namespace App\Support;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;

/**
 * Ready-made starting points for common events. A template fills in settings
 * and categories (and, for motions, the Yes/No/Abstain options); the organiser
 * adds their own candidates afterwards.
 */
class Templates
{
    public static function all(): array
    {
        return [
            'student_union' => [
                'name' => 'Student union election',
                'summary' => 'Executive posts, one choice each. Results after voting closes.',
                'icon' => 'bi-mortarboard',
                'results_visibility' => Event::RESULTS_AFTER_CLOSE,
                'voting_mode' => Event::MODE_CODES,
                'categories' => [
                    ['name' => 'President'], ['name' => 'Vice President'], ['name' => 'General Secretary'],
                    ['name' => 'Financial Secretary'], ['name' => 'Treasurer'], ['name' => 'Public Relations Officer'],
                    ['name' => 'Social Director'],
                ],
            ],
            'award_night' => [
                'name' => 'Award night',
                'summary' => 'Paid public voting: fans pay per vote. Live leaderboard.',
                'icon' => 'bi-trophy',
                'results_visibility' => Event::RESULTS_LIVE,
                'voting_mode' => Event::MODE_PAID,
                'vote_price_naira' => 50,
                'categories' => [
                    ['name' => 'Most Popular'], ['name' => 'Best Dressed (Male)'], ['name' => 'Best Dressed (Female)'],
                    ['name' => 'Personality of the Year'], ['name' => 'Rising Star'],
                ],
            ],
            'agm' => [
                'name' => 'AGM / association',
                'summary' => 'Officers plus motions with Yes / No / Abstain.',
                'icon' => 'bi-people',
                'results_visibility' => Event::RESULTS_AFTER_CLOSE,
                'voting_mode' => Event::MODE_CODES,
                'categories' => [
                    ['name' => 'Chairperson'], ['name' => 'Secretary'], ['name' => 'Treasurer'],
                    ['name' => 'Motion: Approve the annual report', 'candidates' => ['Yes', 'No', 'Abstain']],
                    ['name' => 'Motion: Approve the new dues', 'candidates' => ['Yes', 'No', 'Abstain']],
                ],
            ],
            'board' => [
                'name' => 'Board / committee',
                'summary' => 'Choose several members from one list (pick up to 3).',
                'icon' => 'bi-diagram-3',
                'results_visibility' => Event::RESULTS_AFTER_CLOSE,
                'voting_mode' => Event::MODE_CODES,
                'categories' => [
                    ['name' => 'Board members', 'max_choices' => 3, 'shuffle_candidates' => true],
                ],
            ],
        ];
    }

    /** Public listing for the create page (no internals). */
    public static function catalogue(): array
    {
        return collect(self::all())->map(fn ($t, $key) => [
            'key' => $key,
            'name' => $t['name'],
            'summary' => $t['summary'],
            'icon' => $t['icon'],
            'voting_mode' => $t['voting_mode'],
            'results_visibility' => $t['results_visibility'],
            'vote_price_naira' => $t['vote_price_naira'] ?? null,
            'categories' => array_column($t['categories'], 'name'),
        ])->values()->all();
    }

    public static function apply(Event $event, string $key): void
    {
        $template = self::all()[$key] ?? null;
        if (! $template) {
            return;
        }
        foreach ($template['categories'] as $c) {
            $category = Category::create([
                'event_id' => $event->id,
                'name' => $c['name'],
                'max_choices' => $c['max_choices'] ?? 1,
                'shuffle_candidates' => $c['shuffle_candidates'] ?? false,
                'method' => $c['method'] ?? Category::PLURALITY,
            ]);
            foreach ($c['candidates'] ?? [] as $name) {
                Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => $name]);
            }
        }
    }
}
