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

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Campus Election',
            'link_token' => Str::uuid()->toString(),
            'expected_voters' => 100,
        ]);

        $categories = collect(['President', 'Secretary'])->map(function ($name) use ($event) {
            return Category::create(['event_id' => $event->id, 'name' => $name]);
        })->values();

        foreach ($categories as $category) {
            Candidate::create([
                'event_id' => $event->id,
                'category_id' => $category->id,
                'name' => $category->name . ' A',
                'image_path' => null,
            ]);
            Candidate::create([
                'event_id' => $event->id,
                'category_id' => $category->id,
                'name' => $category->name . ' B',
                'image_path' => null,
            ]);
        }

        Event::create([
            'user_id' => $user->id,
            'name' => 'Campus Election',
            'link_token' => Str::uuid()->toString(),
            'expected_voters' => 50,
        ]);
    }
}
