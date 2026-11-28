<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([UserSeeder::class]);

        // Sample events only on a developer's machine, never on a live server.
        if (app()->environment('local')) {
            $this->call([EventSeeder::class]);
        }
    }
}
