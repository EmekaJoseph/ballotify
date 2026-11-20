<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Flutterwave client (Standard checkout), used as the backup when
 * Paystack can't start a payment. https://developer.flutterwave.com/docs
 *
 * verify() returns the transaction in the same shape as Paystack::verify()
 * (status 'success', amount in kobo, reference, currency, channel, paid_at),
 * so both providers share the same settlement code.
 */
class Flutterwave
{
    public static function configured(): bool
    {
        return filled(config('services.flutterwave.secret_key'));
    }

    /** Returns the hosted checkout link the payer is sent to. */
    public static function initialize(string $email, ?string $name, int $amountKobo, string $reference, string $redirectUrl, array $meta = [], string $title = ''): string
    {
        $res = self::client()->post('/payments', [
            'tx_ref' => $reference,
            'amount' => round($amountKobo / 100, 2),
            'currency' => config('ballotify.billing.currency', 'NGN'),
            'redirect_url' => $redirectUrl,
            'customer' => array_filter(['email' => $email, 'name' => $name]),
            // Flutterwave meta values must be scalars.
            'meta' => array_map(fn ($v) => is_scalar($v) ? $v : json_encode($v), $meta),
            'customizations' => array_filter(['title' => mb_substr($title ?: (string) config('app.name', 'Ballotify'), 0, 60)]),
        ]);

        $url = $res->json('data.link');
        if (! $res->successful() || $res->json('status') !== 'success' || ! $url) {
            throw new RuntimeException('Flutterwave initialize failed: ' . ($res->json('message') ?? $res->status()));
        }

        return $url;
    }

    /**
     * The transaction as Flutterwave sees it, normalised to Paystack's shape.
     * A payment the payer never attempted doesn't exist yet: reported as pending.
     */
    public static function verify(string $reference): array
    {
        $res = self::client()->get('/transactions/verify_by_reference', ['tx_ref' => $reference]);

        if (in_array($res->status(), [400, 404], true) && $res->json('status') === 'error') {
            return ['status' => 'pending', 'reference' => $reference];
        }
        if (! $res->successful() || $res->json('status') !== 'success') {
            throw new RuntimeException('Flutterwave verify failed: ' . ($res->json('message') ?? $res->status()));
        }

        $d = (array) $res->json('data');
        return [
            'status' => match ($d['status'] ?? null) {
                'successful' => 'success',
                'failed' => 'failed',
                default => 'pending',
            },
            'reference' => $d['tx_ref'] ?? null,
            'amount' => (int) round(((float) ($d['amount'] ?? 0)) * 100),
            'currency' => $d['currency'] ?? null,
            'channel' => isset($d['payment_type']) ? str_replace('_', ' ', (string) $d['payment_type']) : null,
            'paid_at' => $d['created_at'] ?? null,
            'gateway_response' => $d['processor_response'] ?? null,
        ];
    }

    /** Webhooks carry the secret hash set in the Flutterwave dashboard in the `verif-hash` header. */
    public static function validWebhook(?string $hash): bool
    {
        $secret = (string) config('services.flutterwave.webhook_hash');
        return $secret !== '' && $hash !== null && hash_equals($secret, $hash);
    }

    private static function client()
    {
        return Http::baseUrl(rtrim((string) config('services.flutterwave.base_url'), '/'))
            ->withToken((string) config('services.flutterwave.secret_key'))
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 300, throw: false);
    }
}
