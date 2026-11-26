<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Stops code guessing without slowing honest voters. Only WRONG codes (or member IDs)
 * count, per IP address per event, over a 10-minute window.
 *
 * The allowance grows with honest use: MAX_MISSES, plus one for every correct code
 * from the same network. A full hall on one Wi-Fi makes plenty of typos and is never
 * blocked by them; someone guessing gains nothing, because more allowance needs real
 * codes. Voter codes are 8 random characters (about 650 billion possibilities), so
 * this mostly makes guessing pointless rather than merely slow.
 */
class CodeGuard
{
    /** Wrong codes allowed per IP address per event per window, before counting correct ones. */
    public const MAX_MISSES = 30;
    public const WINDOW_SECONDS = 600;

    private static function key(Request $request, Event $event, string $kind, string $what): string
    {
        return "{$what}:{$kind}:{$event->id}:" . sha1((string) $request->ip());
    }

    /** A 429 response when this network has missed too often, else null. */
    public static function blocked(Request $request, Event $event, string $kind = 'code'): ?JsonResponse
    {
        $missKey = self::key($request, $event, $kind, 'miss');
        $allowed = self::MAX_MISSES + RateLimiter::attempts(self::key($request, $event, $kind, 'ok'));
        if (RateLimiter::attempts($missKey) < $allowed) {
            return null;
        }
        $wait = max(1, RateLimiter::availableIn($missKey));
        return response()->json([
            'message' => __('Too many wrong codes from this network. Please wait :minutes minutes and try again, or ask the organiser for help.', ['minutes' => max(1, (int) ceil($wait / 60))]),
            'retry_after' => $wait,
        ], 429)->header('Retry-After', (string) $wait);
    }

    /** Record one wrong code (or member ID) from this network. */
    public static function miss(Request $request, Event $event, string $kind = 'code'): void
    {
        RateLimiter::hit(self::key($request, $event, $kind, 'miss'), self::WINDOW_SECONDS);
    }

    /** Record one correct code from this network (it earns room for others' typos). */
    public static function ok(Request $request, Event $event, string $kind = 'code'): void
    {
        RateLimiter::hit(self::key($request, $event, $kind, 'ok'), self::WINDOW_SECONDS);
    }
}
