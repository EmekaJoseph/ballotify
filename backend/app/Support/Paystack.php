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

    /**
     * Returns the hosted checkout URL the payer is sent to.
     * $split: ['subaccount' => code, 'transaction_charge' => kobo for the platform]
     * sends the rest straight to the organiser (Paystack split payments).
     */
    public static function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = [], array $split = []): string
    {
        $body = [
            'email' => $email,
            'amount' => $amountKobo,
            'currency' => config('ballotify.billing.currency', 'NGN'),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ];
        if (! empty($split['subaccount'])) {
            $body['subaccount'] = $split['subaccount'];
            // Our share is a flat amount; Paystack's own fee comes out of the organiser's share.
            $body['transaction_charge'] = (int) ($split['transaction_charge'] ?? 0);
            $body['bearer'] = 'subaccount';
        }
        $res = self::client()->post('/transaction/initialize', $body);

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

    /** Nigerian banks: [ { name, code } ], cached for a day. */
    public static function banks(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('paystack.banks.ng', now()->addDay(), function () {
            $res = self::client()->get('/bank', ['country' => 'nigeria', 'perPage' => 500]);
            if (! $res->successful() || ! $res->json('status')) {
                throw new RuntimeException('Paystack bank list failed: ' . ($res->json('message') ?? $res->status()));
            }
            return collect($res->json('data'))
                ->filter(fn ($b) => ($b['active'] ?? true) && ! ($b['is_deleted'] ?? false))
                ->map(fn ($b) => ['name' => $b['name'], 'code' => (string) $b['code']])
                ->unique('code')->sortBy('name')->values()->all();
        });
    }

    /** The account holder's name for a bank account, or null if it doesn't resolve. */
    public static function resolveAccount(string $accountNumber, string $bankCode): ?string
    {
        $res = self::client()->get('/bank/resolve', ['account_number' => $accountNumber, 'bank_code' => $bankCode]);
        return $res->successful() && $res->json('status') ? (string) $res->json('data.account_name') : null;
    }

    /** Create (or update) the organiser's subaccount. Returns its code. */
    public static function upsertSubaccount(?string $existingCode, string $businessName, string $bankCode, string $accountNumber, float $percentage): string
    {
        $body = [
            'business_name' => mb_substr($businessName, 0, 100),
            'settlement_bank' => $bankCode,
            'account_number' => $accountNumber,
            // Default share for Paystack; each payment overrides it with a flat transaction_charge.
            'percentage_charge' => $percentage,
        ];
        $res = $existingCode
            ? self::client()->put('/subaccount/' . rawurlencode($existingCode), $body)
            : self::client()->post('/subaccount', $body);
        $code = $res->json('data.subaccount_code') ?? $existingCode;
        if (! $res->successful() || ! $res->json('status') || ! $code) {
            throw new RuntimeException('Paystack subaccount failed: ' . ($res->json('message') ?? $res->status()));
        }
        return $code;
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
