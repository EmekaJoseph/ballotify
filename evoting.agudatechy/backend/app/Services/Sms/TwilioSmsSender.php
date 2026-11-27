<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

/** https://www.twilio.com/docs/messaging/api/message-resource#create-a-message-resource */
class TwilioSmsSender implements SmsSender
{
    public function __construct(
        private readonly ?string $sid,
        private readonly ?string $token,
        private readonly ?string $from,
    ) {}

    public function send(string $to, string $message): void
    {
        Http::asForm()
            ->withBasicAuth((string) $this->sid, (string) $this->token)
            ->timeout(20)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", [
                'To' => $to,
                'From' => $this->from,
                'Body' => $message,
            ])
            ->throw();
    }

    public function isLive(): bool
    {
        return $this->sid && $this->token && $this->from;
    }

    public function name(): string
    {
        return 'Twilio';
    }
}
