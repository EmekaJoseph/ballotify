<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/** Grant or revoke access to the platform-owner area. */
class MakeAdmin extends Command
{
    protected $signature = 'ballotify:make-admin {email : Email of an existing account} {--revoke : Remove admin rights instead}';

    protected $description = 'Give an account access to the platform admin area (or remove it)';

    public function handle(): int
    {
        $user = User::where('email', strtolower((string) $this->argument('email')))->first();
        if (! $user) {
            $this->error('No account with that email. Sign up first, then run this again.');
            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');
        $user->forceFill(['is_admin' => ! $revoke])->save();
        \App\Support\AccountLogger::record($revoke ? 'admin.revoked' : 'admin.granted',
            $revoke ? 'Admin rights removed (server command)' : 'Made a platform admin (server command)', $user, null);
        $this->info($revoke ? "{$user->email} is no longer an admin." : "{$user->email} is now an admin.");

        return self::SUCCESS;
    }
}
