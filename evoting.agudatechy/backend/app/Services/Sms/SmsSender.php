<?php

namespace App\Services\Sms;

interface SmsSender
{
    /**
     * Send a text message. Throws on failure.
     *
     * @param  string  $to  Phone number in international format (+234…).
     */
    public function send(string $to, string $message): void;

    /** Whether real credentials are configured (false = test/log mode). */
    public function isLive(): bool;

    /** Short provider name for the UI, e.g. "Termii". */
    public function name(): string;
}
