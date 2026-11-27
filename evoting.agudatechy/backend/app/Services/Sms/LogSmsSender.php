<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/** Test mode: writes messages to storage/logs/laravel.log instead of sending them. */
class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): void
    {
        Log::info('[SMS test mode] message not sent', ['to' => $to, 'message' => $message]);
    }

    public function isLive(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'Test mode (log)';
    }
}
