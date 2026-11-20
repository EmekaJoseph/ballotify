<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Cloudflare Turnstile: a free, mostly invisible check that a real person is
 * using the form (stops bots and card-testing on paid votes).
 * Off until TURNSTILE_SECRET_KEY is set, so local development just works.
 */
class Turnstile
{
    public static function enabled(): bool
    {
        return filled(config('services.turnstile.secret_key'));
    }

    /** Throws a 422 when the check is on and the token is missing or rejected. */
    public static function verify(Request $request): void
    {
        if (! self::enabled()) {
            return;
        }
        $token = (string) $request->input('captcha_token', '');
        $fail = fn () => throw ValidationException::withMessages([
            'captcha_token' => ['Please complete the “I’m human” check and try again.'],
        ]);
        if ($token === '') {
            $fail();
        }

        try {
            $res = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
            $ok = $res->successful() && $res->json('success') === true;
        } catch (\Throwable $e) {
            // Cloudflare unreachable: don't lock real people out; the rate limits still apply.
            report($e);
            return;
        }

        if (! $ok) {
            $fail();
        }
    }
}
