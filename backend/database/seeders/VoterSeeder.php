<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Voter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class VoterSeeder extends Seeder
{
    public function run(): void
    {
        // Paid-voting events don't use codes.
        $events = Event::where('voting_mode', Event::MODE_CODES)->get();
        if ($events->isEmpty()) {
            return;
        }
        $all = [];
        foreach ($events as $event) {
            $plain = [];
            for ($i = 1; $i <= 200; $i++) {
                $code = 'E'.$event->id.'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
                Voter::create([
                    'event_id' => $event->id,
                    'name' => 'Voter '.$i,
                    // example.com is reserved for documentation: it never reaches a real inbox.
                    // No demo phone numbers: made-up numbers could belong to real people.
                    'email' => "voter{$i}.event{$event->id}@example.com",
                    'code' => $code,
                ]);
                $plain[] = ['name' => 'Voter '.$i, 'code' => $code];
            }
            $all[] = ['event_id' => $event->id, 'codes' => $plain];
        }
        Storage::disk('local')->put('seed_voter_codes.json', json_encode(['events' => $all], JSON_PRETTY_PRINT));
        $this->command?->info('Seeded 200 voters per event. Codes in storage/app/private/seed_voter_codes.json');
    }
}
