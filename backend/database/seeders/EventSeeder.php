<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $now = now();
        // 0: open now (choose one per post) · 1: closed (board picks up to 2) · 2: opens tomorrow
        $events = [
            ['name' => 'Campus Election', 'visibility' => 'live', 'max' => 1,
                'starts_at' => $now->copy()->subHours(1), 'ends_at' => $now->copy()->addDays(1)],
            ['name' => 'Faculty Awards', 'visibility' => 'after_close', 'max' => 2,
                'starts_at' => $now->copy()->subDays(3), 'ends_at' => $now->copy()->subDay()],
            ['name' => 'Tech Fest Poll', 'visibility' => 'after_close', 'max' => 2,
                'starts_at' => $now->copy()->addDay(), 'ends_at' => $now->copy()->addDays(3)],
        ];

        foreach ($events as $idx => $spec) {
            $event = Event::create([
                'user_id' => $user->id,
                'name' => $spec['name'],
                'link_token' => Str::uuid()->toString(),
                'results_visibility' => $spec['visibility'],
                'starts_at' => $spec['starts_at'],
                'ends_at' => $spec['ends_at'],
            ]);
            $categories = collect(['President', 'Vice President', 'Secretary', 'Treasurer'])
                ->map(fn ($name) => Category::create([
                    'event_id' => $event->id,
                    'name' => $name,
                    // Treasurer shows off ranked choice on the two events that have votes.
                    'max_choices' => $name === 'Treasurer' && $idx < 2 ? null : $spec['max'],
                    'method' => $name === 'Treasurer' && $idx < 2 ? Category::RANKED : Category::PLURALITY,
                    'shuffle_candidates' => $idx === 0 && $name === 'President',
                ]))->values();
            // Some candidates get a short description, some don't (it's optional).
            $blurbs = [
                'A' => 'Final-year Law · campaigning for longer library hours',
                'B' => 'Second-year Engineering · wants a stronger student voice',
                'C' => null,
            ];
            foreach ($categories as $category) {
                foreach ($blurbs as $suffix => $blurb) {
                    Candidate::create([
                        'event_id' => $event->id,
                        'category_id' => $category->id,
                        'name' => $category->name . ' ' . $suffix,
                        'description' => $blurb,
                    ]);
                }
            }
        }

        // Paid public voting demo (award night): fans pay ₦50 per vote.
        $awards = Event::create([
            'user_id' => $user->id,
            'name' => 'Campus Awards Night',
            'description' => 'Vote for your favourites. ₦50 per vote, as many votes as you like.',
            'link_token' => Str::uuid()->toString(),
            'results_visibility' => 'live',
            'voting_mode' => Event::MODE_PAID,
            'vote_price_naira' => 50,
            'starts_at' => $now->copy()->subDays(2),
            'ends_at' => $now->copy()->addDays(2),
        ]);
        $nominees = [
            'Most Popular' => ['Amaka Obi', 'Tunde Bello', 'Zainab Musa'],
            'Best Dressed' => ['Chioma Eze', 'David Okon', 'Fatima Sani'],
            'Rising Star' => ['Ifeanyi Nwosu', 'Kemi Adeyemi', 'Musa Ibrahim'],
        ];
        foreach ($nominees as $categoryName => $names) {
            $category = Category::create(['event_id' => $awards->id, 'name' => $categoryName]);
            foreach ($names as $name) {
                Candidate::create(['event_id' => $awards->id, 'category_id' => $category->id, 'name' => $name]);
            }
        }
    }
}
