<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Paystack client: start a checkout, verify a transaction, check a
 * webhook signature. https://paystack.com/docs/api/transaction/
 */
class Paystack
{
    public static function configured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    /** Returns the hosted checkout URL the organiser is sent to. */
    public static function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): string
    {
        $res = self::client()->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $amountKobo,
            'currency' => config('ballotify.billing.currency', 'NGN'),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ]);

        $url = $res->json('data.authorization_url');
        if (! $res->successful() || ! $res->json('status') || ! $url) {
            throw new RuntimeException('Paystack initialize failed: ' . ($res->json('message') ?? $res->status()));
        }

        return $url;
    }

    /** The transaction as Paystack sees it (`status`, `amount`, `currency`, `reference`, ...). */
    public static function verify(string $reference): array
    {
        $res = self::client()->get('/transaction/verify/' . rawurlencode($reference));
        if (! $res->successful() || ! $res->json('status')) {
            throw new RuntimeException('Paystack verify failed: ' . ($res->json('message') ?? $res->status()));
        }

        return (array) $res->json('data');
    }

    /** Webhooks are signed with HMAC-SHA512 of the raw body using the secret key. */
    public static function validSignature(string $payload, ?string $signature): bool
    {
        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '' || ! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }

    private static function client()
    {
        return Http::baseUrl(rtrim((string) config('services.paystack.base_url'), '/'))
            ->withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 300, throw: false);
    }
}
