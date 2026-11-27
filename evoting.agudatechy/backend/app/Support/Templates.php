<?php

namespace App\Support;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;

/**
 * Ready-made starting points for common elections. A template fills in settings
 * and categories; the organiser adds their own candidates afterwards.
 */
class Templates
{
    public static function all(): array
    {
        return [
            'church' => [
                'name' => 'Church / ministry',
                'summary' => 'Council or elders (pick several) and officers.',
                'icon' => 'bi-house-heart',
                'results_visibility' => Event::RESULTS_AFTER_CLOSE,
                'categories' => [
                    ['name' => 'Church Council', 'max_choices' => 5, 'shuffle_candidates' => true],
                    ['name' => 'Secretary'], ['name' => 'Treasurer'],
                ],
            ],
            'student_union' => [
                'name' => 'School / student union',
                'summary' => 'Executive posts, one choice each. Results after voting closes.',
                'icon' => 'bi-mortarboard',
                'results_visibility' => Event::RESULTS_AFTER_CLOSE,
                'categories' => [
                    ['name' => 'President'], ['name' => 'Vice President'], ['name' => 'General Secretary'],
                    ['name' => 'Financial Secretary'], ['name' => 'Treasurer'], ['name' => 'Public Relations Officer'],
                    ['name' => 'Social Director'],
                ],
            ],
            'association' => [
                'name' => 'Association / cooperative',
                'summary' => 'Executive officers, one choice each.',
                'icon' => 'bi-people',
                'results_visibility' => Event::RESULTS_AFTER_CLOSE,
                'categories' => [
                    ['name' => 'Chairperson'], ['name' => 'Vice Chairperson'], ['name' => 'Secretary'], ['name' => 'Treasurer'],
                ],
            ],
            'board' => [
                'name' => 'Board / committee',
                'summary' => 'Choose several members from one list (pick up to 3).',
                'icon' => 'bi-diagram-3',
                'results_visibility' => Event::RESULTS_AFTER_CLOSE,
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
            'voting_mode' => Event::MODE_CODES,
            'results_visibility' => $t['results_visibility'],
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
                'method' => Category::PLURALITY,
            ]);
            foreach ($c['candidates'] ?? [] as $name) {
                Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => $name]);
            }
        }
    }
}
