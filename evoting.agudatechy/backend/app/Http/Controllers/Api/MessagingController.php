<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/** Tells the dashboard whether email will really be delivered or only logged. */
class MessagingController extends Controller
{
    public function status()
    {
        $mailer = (string) config('mail.default');

        return response()->json([
            'email' => [
                'live' => ! in_array($mailer, ['log', 'array'], true),
                'provider' => $mailer,
            ],
            // 'sync' sends immediately; anything else needs `php artisan queue:work` running.
            'queue' => config('queue.default'),
        ]);
    }
}
