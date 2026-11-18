<?php

namespace App\Services\Sms;

/**
 * Picks the SMS provider from SMS_DRIVER (log | twilio | termii | africastalking).
 * A provider without credentials falls back to test mode so nothing breaks
 * before the organiser has set up an account.
 */
class SmsManager
{
    public static function make(): SmsSender
    {
        $driver = strtolower((string) config('services.sms.driver', 'log'));

        $sender = match ($driver) {
            'twilio' => new TwilioSmsSender(
                config('services.twilio.sid'),
                config('services.twilio.token'),
                config('services.twilio.from'),
            ),
            'termii' => new TermiiSmsSender(
                config('services.termii.api_key'),
                config('services.termii.sender_id'),
                (string) config('services.termii.base_url', 'https://api.ng.termii.com'),
                (string) config('services.termii.channel', 'generic'),
            ),
            'africastalking' => new AfricasTalkingSmsSender(
                config('services.africastalking.username'),
                config('services.africastalking.api_key'),
                config('services.africastalking.from'),
            ),
            default => new LogSmsSender(),
        };

        return $sender->isLive() ? $sender : new LogSmsSender();
    }
}
