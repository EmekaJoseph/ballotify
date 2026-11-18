<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            ['name' => 'Admin', 'password' => 'password'],
        );
        // Demo account starts verified so it can send invitations straight away.
        $user->forceFill(['email_verified_at' => now()])->save();
    }
}
