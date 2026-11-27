<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/** Suspended organisers lose access straight away, even with a valid token. */
class EnsureNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->isSuspended()) {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }
            return response()->json([
                'message' => 'This account has been suspended. Contact support if you think this is a mistake.',
                'suspended' => true,
            ], 403);
        }

        return $next($request);
    }
}
