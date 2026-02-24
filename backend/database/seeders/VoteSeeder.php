<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Database\Seeder;

class VoteSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::first();
        if (! $event) {
            return;
        }

        $categories = Category::where('event_id', $event->id)->get();
        if ($categories->isEmpty()) {
            return;
        }

        $voters = Voter::where('event_id', $event->id)->orderBy('id')->take(3)->get();
        foreach ($voters as $voter) {
            foreach ($categories as $category) {
                $candidate = Candidate::where('event_id', $event->id)
                    ->where('category_id', $category->id)
                    ->orderBy('id')
                    ->first();
                if ($candidate) {
                    Vote::create([
                        'event_id' => $event->id,
                        'voter_id' => $voter->id,
                        'category_id' => $category->id,
                        'candidate_id' => $candidate->id,
                        'ip' => '127.0.0.1',
                    ]);
                }
            }
            $voter->used_at = now();
            $voter->save();
        }

        $this->command?->info('Seeded sample votes for 3 voters.');
    }
}

