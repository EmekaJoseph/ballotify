<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sms\SmsSender;

/** Tells the dashboard whether email/SMS will really be delivered or only logged. */
class MessagingController extends Controller
{
    public function status(SmsSender $sms)
    {
        $mailer = (string) config('mail.default');

        return response()->json([
            'email' => [
                'live' => ! in_array($mailer, ['log', 'array'], true),
                'provider' => $mailer,
            ],
            'sms' => [
                'live' => $sms->isLive(),
                'provider' => $sms->name(),
            ],
            // 'sync' sends immediately; anything else needs `php artisan queue:work` running.
            'queue' => config('queue.default'),
        ]);
    }
}
