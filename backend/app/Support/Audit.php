<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Records organiser actions. Logging must never break the action itself,
 * so failures are reported and swallowed.
 */
class Audit
{
    public static function log(Event $event, string $action, string $description, array $meta = []): void
    {
        try {
            AuditLog::create([
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => Str::limit($description, 490),
                'during_voting' => $event->hasVotes(),
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
