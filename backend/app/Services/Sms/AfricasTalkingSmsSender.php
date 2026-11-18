<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** https://developers.africastalking.com/docs/sms/sending/bulk */
class AfricasTalkingSmsSender implements SmsSender
{
    public function __construct(
        private readonly ?string $username,
        private readonly ?string $apiKey,
        private readonly ?string $from,
    ) {}

    public function send(string $to, string $message): void
    {
        $host = $this->username === 'sandbox'
            ? 'https://api.sandbox.africastalking.com'
            : 'https://api.africastalking.com';

        $payload = ['username' => $this->username, 'to' => $to, 'message' => $message];
        if ($this->from) {
            $payload['from'] = $this->from;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->withHeaders(['apiKey' => (string) $this->apiKey])
            ->timeout(20)
            ->post($host . '/version1/messaging', $payload)
            ->throw();

        $status = $response->json('SMSMessageData.Recipients.0.status');
        if ($status !== null && $status !== 'Success') {
            throw new RuntimeException("Africa's Talking: {$status}");
        }
    }

    public function isLive(): bool
    {
        return $this->username && $this->apiKey;
    }

    public function name(): string
    {
        return "Africa's Talking";
    }
}
