<?php

namespace App\Support;

use App\Models\AccountLog;
use App\Models\User;

/**
 * Writes the platform account log. Logging must never break what's being logged,
 * so a failure here is reported and swallowed.
 */
class AccountLogger
{
    /**
     * @param  User|null  $user   the account it concerns
     * @param  User|null  $actor  who did it (defaults to the signed-in user; null = system / server command)
     */
    public static function record(string $action, string $description, ?User $user = null, ?User $actor = null, array $meta = []): void
    {
        try {
            $actor ??= auth()->user();
            AccountLog::create([
                'actor_id' => $actor?->id,
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'action' => $action,
                'description' => mb_substr($description, 0, 500),
                // Only for web requests; server commands have no address.
                'ip' => request()->route() ? request()->ip() : null,
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
