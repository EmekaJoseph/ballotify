<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The platform admin, who approves new accounts. Change this password right after
 * the first sign-in on a live server (Profile -> Password).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@agudatechy.com'],
            ['name' => 'Agudatechy Admin', 'password' => 'password123#'],
        );
        $user->forceFill(['email_verified_at' => now(), 'is_admin' => true, 'approved_at' => now()])->save();
    }
}
