<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Voter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class VoterSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::first();
        if (! $event) {
            return;
        }

        $plain = [];
        for ($i = 1; $i <= 10; $i++) {
            $code = 'VOTE'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            Voter::create([
                'event_id' => $event->id,
                'name' => 'Voter '.$i,
                'code_hash' => Hash::make($code),
            ]);
            $plain[] = ['name' => 'Voter '.$i, 'code' => $code];
        }

        Storage::disk('local')->put('seed_voter_codes.json', json_encode([
            'event_id' => $event->id,
            'codes' => $plain,
        ], JSON_PRETTY_PRINT));

        $this->command?->info('Seeded 10 voters. Codes stored at storage/app/seed_voter_codes.json');
    }
}

