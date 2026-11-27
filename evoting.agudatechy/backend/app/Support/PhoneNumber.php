<?php

namespace App\Support;

/**
 * Normalises phone numbers to international format (+2348012345678).
 *
 * Local numbers starting with 0 are converted using SMS_DEFAULT_COUNTRY_CODE
 * (e.g. 234 for Nigeria): "0801 234 5678" -> "+2348012345678".
 */
class PhoneNumber
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return null;
        }

        if ($hasPlus) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        $country = preg_replace('/\D+/', '', (string) config('services.sms.default_country_code'));
        if (str_starts_with($digits, '0') && $country !== '') {
            return '+' . $country . substr($digits, 1);
        }
        if ($country !== '' && str_starts_with($digits, $country)) {
            return '+' . $digits;
        }
        // A local number that lost its leading 0 (Excel stores 0801… as the number 801…):
        // too short to include a country code, so it gets the default one.
        if ($country !== '' && strlen($digits) >= 7 && strlen($digits) <= 10) {
            return '+' . $country . $digits;
        }

        return $digits;
    }

    /** Human explanation of why a number was rejected (completes "… <problem>"). */
    public static function problem(?string $normalized): string
    {
        if ($normalized !== null && str_starts_with($normalized, '0')) {
            return 'needs a country code, e.g. +234 801 234 5678.';
        }
        return 'is not a valid phone number. Use international format, e.g. +234 801 234 5678.';
    }

    /** True for a plausible E.164 number (8–15 digits after an optional +). */
    public static function isValid(?string $normalized): bool
    {
        return $normalized !== null && (bool) preg_match('/^\+?[1-9]\d{7,14}$/', $normalized);
    }
}
