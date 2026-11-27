<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public voting pages send the voter's language in X-Locale, so messages
 * from the API (code already used, voting closed...) come back in it.
 * Anything not in config('ballotify.languages') is ignored (English).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = strtolower(substr((string) $request->header('X-Locale'), 0, 5));
        // Always set it, so one request's language never carries over to the next
        // in a long-running worker (Octane, queue workers, tests).
        App::setLocale(in_array($locale, config('ballotify.languages', ['en']), true)
            ? $locale
            : (string) config('app.fallback_locale', 'en'));

        return $next($request);
    }
}
