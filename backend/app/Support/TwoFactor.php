<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/** Authenticator-app (TOTP) two-step login. */
class TwoFactor
{
    private const CHALLENGE_TTL = 300; // seconds to enter the code after the password

    public static function newSecret(): string
    {
        return (new Google2FA())->generateSecretKey(32);
    }

    public static function otpauthUrl(User $user, string $secret): string
    {
        return (new Google2FA())->getQRCodeUrl((string) config('app.name', 'Ballotify'), $user->email, $secret);
    }

    public static function verifyTotp(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        // window 1 = accept the previous/next 30-second code for clock drift
        return ctype_digit($code) && (new Google2FA())->verifyKey($secret, $code, 1);
    }

    /** @return string[] */
    public static function newRecoveryCodes(): array
    {
        return collect(range(1, 8))->map(fn () => Str::lower(Str::random(5) . '-' . Str::random(5)))->all();
    }

    /** Accepts an app code, or consumes one recovery code. */
    public static function verify(User $user, string $code): bool
    {
        if ($user->two_factor_secret && self::verifyTotp($user->two_factor_secret, $code)) {
            return true;
        }
        $codes = $user->two_factor_recovery_codes ?? [];
        $given = Str::lower(trim($code));
        foreach ($codes as $i => $recovery) {
            if (hash_equals($recovery, $given)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                return true;
            }
        }
        return false;
    }

    public static function makeChallenge(User $user): string
    {
        return Crypt::encryptString(json_encode(['uid' => $user->id, 'exp' => time() + self::CHALLENGE_TTL]));
    }

    public static function userFromChallenge(string $challenge): ?User
    {
        try {
            $data = json_decode(Crypt::decryptString($challenge), true);
        } catch (\Throwable) {
            return null;
        }
        if (! is_array($data) || ($data['exp'] ?? 0) < time()) {
            return null;
        }
        $user = User::find($data['uid'] ?? 0);
        return $user && $user->two_factor_enabled ? $user : null;
    }
}
