<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** https://developers.termii.com/messaging-api */
class TermiiSmsSender implements SmsSender
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $senderId,
        private readonly string $baseUrl,
        private readonly string $channel,
    ) {}

    public function send(string $to, string $message): void
    {
        $response = Http::acceptJson()
            ->timeout(20)
            ->post(rtrim($this->baseUrl, '/') . '/api/sms/send', [
                'api_key' => $this->apiKey,
                'to' => ltrim($to, '+'),   // Termii expects digits only
                'from' => $this->senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => $this->channel, // "generic" or "dnd" (reaches DND numbers in Nigeria)
            ])
            ->throw();

        if (($response->json('code') ?? 'ok') !== 'ok') {
            throw new RuntimeException('Termii: ' . ($response->json('message') ?? 'message rejected'));
        }
    }

    public function isLive(): bool
    {
        return $this->apiKey && $this->senderId;
    }

    public function name(): string
    {
        return 'Termii';
    }
}
