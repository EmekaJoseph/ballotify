<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

        if (! DB::table('event_types')->where('key', 'single')->exists()) {
            DB::table('event_types')->insert([
                ['key' => 'single', 'name' => 'Single Choice', 'created_at' => now(), 'updated_at' => now()],
                ['key' => 'multiple', 'name' => 'Multiple Choice', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        $singleId = DB::table('event_types')->where('key', 'single')->value('id');
        $multipleId = DB::table('event_types')->where('key', 'multiple')->value('id');

        $names = ['Campus Election', 'Faculty Awards', 'Tech Fest Poll'];
        foreach ($names as $idx => $ename) {
            $now = now();
            // Event windows: 0-active, 1-expired, 2-upcoming
            $windows = [
                ['starts_at' => $now->copy()->subHours(1), 'ends_at' => $now->copy()->addDays(1)],
                ['starts_at' => $now->copy()->subDays(3), 'ends_at' => $now->copy()->subDay()],
                ['starts_at' => $now->copy()->addDay(), 'ends_at' => $now->copy()->addDays(3)],
            ];
            $win = $windows[$idx] ?? ['starts_at' => null, 'ends_at' => null];
            $event = Event::create([
                'user_id' => $user->id,
                'name' => $ename,
                'link_token' => Str::uuid()->toString(),
                'expected_voters' => [100, 150, 200][$idx] ?? 100,
                'event_type_id' => $idx === 0 ? $singleId : $multipleId,
                'starts_at' => $win['starts_at'],
                'ends_at' => $win['ends_at'],
            ]);
            $categories = collect(['President', 'Vice President', 'Secretary', 'Treasurer'])->map(function ($name) use ($event) {
                return Category::create(['event_id' => $event->id, 'name' => $name]);
            })->values();
            foreach ($categories as $category) {
                foreach (['A', 'B', 'C'] as $suffix) {
                    Candidate::create([
                        'event_id' => $event->id,
                        'category_id' => $category->id,
                        'name' => $category->name . ' ' . $suffix,
                        'image_path' => null,
                    ]);
                }
            }
        }
    }
}
