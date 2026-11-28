<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventMember;
use App\Models\User;
use App\Support\AccountLogger;
use App\Support\Turnstile;
use App\Support\TwoFactor;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use function Illuminate\Support\defer;

class AuthController extends Controller
{
    // ------------------------------------------------------------------ sign up / in

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Please accept the Terms and Privacy Policy to continue.',
            'email.unique' => 'An account with this email already exists. Sign in instead, or reset your password if you’ve forgotten it.',
        ]);
        Turnstile::verify($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'], // hashed by the model cast
        ]);

        // Sent after the response: a slow or broken mail server must never make a sign-up that
        // succeeded look failed (the person would retry and get "email already taken").
        defer(fn () => $this->safely(fn () => $user->sendEmailVerificationNotification()));
        AccountLogger::record('account.created', 'Signed up', $user, $user);

        return response()->json(['token' => $this->issueToken($user), 'user' => $user], 201);
    }

    /**
     * Step 1 of sign-in. Accounts with two-step login get a short-lived
     * challenge instead of a token, to be completed at /auth/two-factor.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', strtolower($data['email']))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // Only for real accounts: typos of unknown addresses aren't worth keeping.
            if ($user) AccountLogger::record('auth.login_failed', 'Sign-in failed: wrong password', $user, null);
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }
        $this->ensureNotSuspended($user);

        if ($user->two_factor_enabled) {
            return response()->json(['two_factor' => true, 'challenge' => TwoFactor::makeChallenge($user)]);
        }

        AccountLogger::record('auth.login', 'Signed in', $user, $user);
        return response()->json(['token' => $this->issueToken($user), 'user' => $user]);
    }

    /** Step 2 of sign-in for accounts with two-step login. */
    public function twoFactorChallenge(Request $request)
    {
        $data = $request->validate([
            'challenge' => ['required', 'string'],
            'code' => ['required', 'string', 'max:64'],
        ]);

        $user = TwoFactor::userFromChallenge($data['challenge']);
        if (! $user) {
            throw ValidationException::withMessages(['code' => ['This sign-in attempt expired. Please sign in again.']]);
        }
        // Per account, so guessing from many addresses doesn't help.
        if (TwoFactor::lockedOut($user)) {
            throw ValidationException::withMessages(['code' => ['Too many wrong codes. Wait ' . TwoFactor::LOCK_MINUTES . ' minutes, then sign in again.']]);
        }
        if (! TwoFactor::verify($user, $data['code'])) {
            TwoFactor::recordFailure($user);
            AccountLogger::record('auth.login_failed', 'Sign-in failed: wrong two-step code', $user, null);
            throw ValidationException::withMessages(['code' => ['That code is not valid. Try the latest code from your app, or a recovery code.']]);
        }
        TwoFactor::clearFailures($user);

        $this->ensureNotSuspended($user);

        AccountLogger::record('auth.login', 'Signed in with two-step login', $user, $user);
        return response()->json(['token' => $this->issueToken($user), 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /** The dashboard's first-run tour was seen (or skipped). */
    public function onboarded(Request $request)
    {
        $user = $request->user();
        if (! $user->onboarded_at) {
            $user->forceFill(['onboarded_at' => now()])->save();
        }
        return response()->json($user);
    }

    // ------------------------------------------------------------------ profile

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            // The sign-in email is fixed once the account is created.
            'email' => ['prohibited'],
            'current_password' => ['required_with:password', 'nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8'],
            // Where paid-voting money is sent.
            'payout_bank_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'payout_account_number' => ['sometimes', 'nullable', 'digits:10'],
            'payout_account_name' => ['sometimes', 'nullable', 'string', 'max:120'],
        ], ['payout_account_number.digits' => 'Enter the 10-digit account number (NUBAN).']);

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'] ?? '', $user->password)) {
                throw ValidationException::withMessages(['current_password' => ['Your current password is incorrect.']]);
            }
            $user->password = $data['password'];
            // Sign out every other device; keep the session making this change.
            $current = $user->currentAccessToken();
            $currentId = $current instanceof PersonalAccessToken ? $current->getKey() : null;
            $user->tokens()->when($currentId, fn ($q) => $q->whereKeyNot($currentId))->delete();
            AccountLogger::record('account.password', 'Password changed (other devices signed out)', $user);
        }
        if (isset($data['name'])) $user->name = $data['name'];
        foreach (['payout_bank_name', 'payout_account_number', 'payout_account_name'] as $field) {
            if (array_key_exists($field, $data)) $user->{$field} = $data[$field] ?: null;
        }
        $user->save();

        return response()->json($user);
    }

    // ------------------------------------------------------------------ password reset

    /** Always answers the same way, so it can't be used to discover accounts. */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $this->safely(fn () => Password::sendResetLink(['email' => strtolower($request->input('email'))]));

        return response()->json(['message' => 'If an account exists for that email, a reset link is on its way.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            ['email' => strtolower($request->input('email'))] + $request->only('password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete(); // sign out everywhere
                // Following the link proves ownership of the inbox.
                if (! $user->hasVerifiedEmail()) $user->markEmailAsVerified();
                EventMember::claimFor($user);
                event(new PasswordReset($user));
                AccountLogger::record('account.password', 'Password reset with an email link (signed out everywhere)', $user, $user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => ['This reset link is invalid or has expired. Please request a new one.']]);
        }

        return response()->json(['message' => 'Your password has been reset. You can now sign in.']);
    }

    // ------------------------------------------------------------------ email verification

    /** Target of the signed link in the verification email (route has the "signed" middleware). */
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'This verification link is not valid.');
        }
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
        // Co-organiser invitations sent to this address can now be linked.
        EventMember::claimFor($user);
        return response()->json(['message' => 'Email confirmed.', 'verified' => true]);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email is already confirmed.']);
        }
        $sent = $this->safely(fn () => $user->sendEmailVerificationNotification());

        return $sent
            ? response()->json(['message' => 'Confirmation email sent. Check your inbox.'])
            : response()->json(['message' => 'The email could not be sent right now. Please try again later.'], 503);
    }

    // ------------------------------------------------------------------ two-step login setup

    /** Start setup: returns a new secret for the authenticator app (not active until confirmed). */
    public function twoFactorSetup(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password:sanctum']]);
        $user = $request->user();
        $secret = TwoFactor::newSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();

        return response()->json(['secret' => $secret, 'otpauth_url' => TwoFactor::otpauthUrl($user, $secret)]);
    }

    /** Finish setup with a code from the app; returns one-time recovery codes. */
    public function twoFactorConfirm(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:16']]);
        $user = $request->user();
        if (! $user->two_factor_secret || ! TwoFactor::verifyTotp($user->two_factor_secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => ['That code is not valid. Make sure your phone time is correct and try the latest code.']]);
        }
        $codes = TwoFactor::newRecoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();
        AccountLogger::record('account.2fa', 'Two-step login turned on', $user);

        return response()->json(['recovery_codes' => $codes, 'user' => $user->fresh()]);
    }

    /** How many recovery codes are left (shown on the profile page). */
    public function twoFactorStatus(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'enabled' => $user->two_factor_enabled,
            'recovery_codes_left' => $user->two_factor_enabled ? count($user->two_factor_recovery_codes ?? []) : 0,
        ]);
    }

    /** Replace the recovery codes (the old ones stop working). */
    public function twoFactorRecoveryCodes(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password:sanctum']]);
        $user = $request->user();
        abort_unless($user->two_factor_enabled, 422, 'Two-step login is not on.');
        $codes = TwoFactor::newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        AccountLogger::record('account.2fa', 'New two-step recovery codes made (the old ones stopped working)', $user);

        return response()->json(['recovery_codes' => $codes]);
    }

    public function twoFactorDisable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password:sanctum']]);
        $request->user()->forceFill([
            'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
        ])->save();
        AccountLogger::record('account.2fa', 'Two-step login turned off', $request->user());

        return response()->json(['user' => $request->user()->fresh()]);
    }

    // ------------------------------------------------------------------ helpers

    private function ensureNotSuspended(User $user): void
    {
        if ($user->isSuspended()) {
            AccountLogger::record('auth.login_failed', 'Sign-in refused: the account is suspended', $user, null);
            throw ValidationException::withMessages(['email' => ['This account has been suspended. Contact support if you think this is a mistake.']]);
        }
    }

    private function issueToken(User $user): string
    {
        EventMember::claimFor($user);
        return $user->createToken('api')->plainTextToken; // expiry: config('sanctum.expiration')
    }

    /** Run a mail-sending step without letting a mail outage break the request. */
    private function safely(callable $fn): bool
    {
        try {
            $fn();
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}
