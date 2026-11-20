<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Which payment provider takes a payment. Paystack first, Flutterwave as the
 * backup: if Paystack can't start a checkout (outage, timeout), the payer is
 * sent to Flutterwave instead, and Paystack is skipped for a couple of minutes
 * so the next payers don't wait for it to time out again.
 */
class Payments
{
    public const PAYSTACK = 'paystack';
    public const FLUTTERWAVE = 'flutterwave';

    private const DOWN_SECONDS = 120;

    /** Configured providers, in order of preference (PAYMENT_PROVIDERS in .env). */
    public static function providers(): array
    {
        $order = array_map('trim', explode(',', (string) config('ballotify.billing.providers', 'paystack,flutterwave')));
        return array_values(array_filter(array_unique($order), fn ($p) => match ($p) {
            self::PAYSTACK => Paystack::configured(),
            self::FLUTTERWAVE => Flutterwave::configured(),
            default => false,
        }));
    }

    public static function available(): bool
    {
        return self::providers() !== [];
    }

    /**
     * Start a checkout with the first provider that works.
     *
     * @param  array  $split  Paystack only: ['subaccount' => code, 'transaction_charge' => kobo]
     * @return array{0: string, 1: string}  [provider, checkout URL]
     */
    public static function checkout(string $email, ?string $name, int $amountKobo, string $reference, string $callbackUrl, array $meta = [], array $split = [], string $title = ''): array
    {
        $providers = self::providers();
        // Recently failing providers go last rather than being dropped: if all are "down", still try.
        usort($providers, fn ($a, $b) => (int) self::isDown($a) <=> (int) self::isDown($b));

        $last = null;
        foreach ($providers as $provider) {
            try {
                $url = $provider === self::PAYSTACK
                    ? Paystack::initialize($email, $amountKobo, $reference, $callbackUrl, $meta, $split)
                    : Flutterwave::initialize($email, $name, $amountKobo, $reference, $callbackUrl, $meta, $title);
                Cache::forget(self::downKey($provider));
                return [$provider, $url];
            } catch (\Throwable $e) {
                $last = $e;
                Cache::put(self::downKey($provider), true, self::DOWN_SECONDS);
                Log::warning("Payment provider {$provider} failed to start a checkout", ['reference' => $reference, 'error' => $e->getMessage()]);
            }
        }

        throw new RuntimeException('No payment provider could start the checkout', 0, $last);
    }

    /** The transaction from the provider that took it, in Paystack's shape. */
    public static function verify(?string $provider, string $reference): array
    {
        return $provider === self::FLUTTERWAVE ? Flutterwave::verify($reference) : Paystack::verify($reference);
    }

    public static function isDown(string $provider): bool
    {
        return (bool) Cache::get(self::downKey($provider));
    }

    private static function downKey(string $provider): string
    {
        return "payments-down:{$provider}";
    }
}
