<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoteSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::all();
        foreach ($events as $event) {
            $categories = Category::where('event_id', $event->id)->get();
            if ($categories->isEmpty()) {
                continue;
            }
            $typeKey = DB::table('event_types')->where('id', $event->event_type_id)->value('key') ?? 'single';
            $voters = Voter::where('event_id', $event->id)->orderBy('id')->take(20)->get();
            foreach ($voters as $voter) {
                foreach ($categories as $category) {
                    if ($typeKey === 'single') {
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
                    } else {
                        $candidates = Candidate::where('event_id', $event->id)
                            ->where('category_id', $category->id)
                            ->orderBy('id')
                            ->take(2)
                            ->get();
                        foreach ($candidates as $cand) {
                            Vote::create([
                                'event_id' => $event->id,
                                'voter_id' => $voter->id,
                                'category_id' => $category->id,
                                'candidate_id' => $cand->id,
                                'ip' => '127.0.0.1',
                            ]);
                        }
                    }
                }
                $voter->used_at = now();
                $voter->save();
            }
        }
        $this->command?->info('Seeded sample votes for 20 voters per event (single or multiple based on event type).');
    }
}
