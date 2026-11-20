<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use App\Notifications\ResetPasswordLink;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

it('requires accepting the terms to sign up, and sends a verification email', function () {
    Notification::fake();

    $this->postJson('/api/auth/register', ['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret123'])
        ->assertStatus(422)->assertJsonValidationErrors('terms');

    $this->postJson('/api/auth/register', ['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret123', 'terms' => true])
        ->assertCreated();

    $user = User::where('email', 'a@example.com')->first();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmailAddress::class);
});

it('verifies the email through the signed link the frontend forwards', function () {
    config(['app.frontend_url' => 'https://app.example.com']);
    $user = User::factory()->unverified()->create();

    $mailUrl = (new VerifyEmailAddress())->toMail($user)->actionUrl;
    expect($mailUrl)->toStartWith('https://app.example.com/verify-email?link=');

    parse_str(parse_url($mailUrl, PHP_URL_QUERY), $q);
    $this->getJson($q['link'])->assertOk()->assertJsonPath('verified', true);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    // A tampered link is rejected.
    $this->getJson(str_replace('signature=', 'signature=x', $q['link']))->assertForbidden();
});

it('only lets verified organisers send invitations', function () {
    $user = User::factory()->unverified()->create();
    $event = Event::create(['user_id' => $user->id, 'name' => 'E', 'link_token' => 'tok-ver']);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'email', 'audience' => 'pending'])
        ->assertForbidden();
});

it('resets a forgotten password and signs out everywhere', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://app.example.com']);
    $user = User::factory()->create(['email' => 'owner@example.com', 'password' => 'oldpassword']);
    $user->createToken('old-device');

    // Same answer whether or not the account exists (no account discovery).
    $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk();
    $this->postJson('/api/auth/forgot-password', ['email' => 'owner@example.com'])->assertOk();

    $token = null;
    Notification::assertSentTo($user, ResetPasswordLink::class, function ($n) use (&$token, $user) {
        $token = $n->token;
        return str_starts_with($n->toMail($user)->actionUrl, 'https://app.example.com/reset-password?token=');
    });

    $this->postJson('/api/auth/reset-password', [
        'token' => 'wrong', 'email' => 'owner@example.com', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1',
    ])->assertStatus(422);

    $this->postJson('/api/auth/reset-password', [
        'token' => $token, 'email' => 'owner@example.com', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1',
    ])->assertOk();

    expect($user->tokens()->count())->toBe(0);
    $this->postJson('/api/auth/login', ['email' => 'owner@example.com', 'password' => 'newpassword1'])->assertOk();
});

it('signs out other devices when the password is changed', function () {
    $user = User::factory()->create(['password' => 'oldpassword']);
    $user->createToken('other-device');
    $current = $user->createToken('this-device')->plainTextToken;

    $this->withToken($current)->patchJson('/api/me', ['current_password' => 'oldpassword', 'password' => 'newpassword1'])->assertOk();

    expect($user->tokens()->pluck('name')->all())->toBe(['this-device']);
});

it('expires login tokens', function () {
    config(['sanctum.expiration' => 60]);
    $user = User::factory()->create();
    $token = $user->createToken('api');
    $token->accessToken->forceFill(['created_at' => now()->subHours(2)])->save();

    $this->withToken($token->plainTextToken)->getJson('/api/me')->assertUnauthorized();
});

it('supports two-step login with an authenticator app and recovery codes', function () {
    $user = User::factory()->create(['email' => 'tfa@example.com', 'password' => 'secret123']);
    $g = new Google2FA();

    $setup = $this->actingAs($user)->postJson('/api/me/two-factor', ['password' => 'secret123'])->assertOk()->json();
    expect($setup['otpauth_url'])->toStartWith('otpauth://totp/');

    $this->actingAs($user)->postJson('/api/me/two-factor/confirm', ['code' => '000000'])->assertStatus(422);
    $codes = $this->actingAs($user)->postJson('/api/me/two-factor/confirm', ['code' => $g->getCurrentOtp($setup['secret'])])
        ->assertOk()->assertJsonPath('user.two_factor_enabled', true)->json('recovery_codes');
    expect($codes)->toHaveCount(8);

    // Password alone no longer signs in.
    $login = $this->postJson('/api/auth/login', ['email' => 'tfa@example.com', 'password' => 'secret123'])
        ->assertOk()->assertJsonPath('two_factor', true)->assertJsonMissingPath('token')->json();

    $this->postJson('/api/auth/two-factor', ['challenge' => $login['challenge'], 'code' => '123456'])->assertStatus(422);
    $this->postJson('/api/auth/two-factor', ['challenge' => $login['challenge'], 'code' => $g->getCurrentOtp($setup['secret'])])
        ->assertOk()->assertJsonStructure(['token']);

    // A recovery code works once.
    $this->postJson('/api/auth/two-factor', ['challenge' => $login['challenge'], 'code' => $codes[0]])->assertOk();
    $this->postJson('/api/auth/two-factor', ['challenge' => $login['challenge'], 'code' => $codes[0]])->assertStatus(422);

    // A forged challenge is rejected.
    $this->postJson('/api/auth/two-factor', ['challenge' => Str::random(40), 'code' => $codes[1]])->assertStatus(422);

    $this->actingAs($user)->deleteJson('/api/me/two-factor', ['password' => 'wrong'])->assertStatus(422);
    $this->actingAs($user)->deleteJson('/api/me/two-factor', ['password' => 'secret123'])->assertOk()->assertJsonPath('user.two_factor_enabled', false);
});

it('refuses a reused code, locks after repeated wrong codes, and renews recovery codes', function () {
    $user = User::factory()->create(['email' => 'tfa2@example.com', 'password' => 'secret123']);
    $g = new Google2FA();
    $secret = $this->actingAs($user)->postJson('/api/me/two-factor', ['password' => 'secret123'])->json('secret');
    $old = $this->actingAs($user)->postJson('/api/me/two-factor/confirm', ['code' => $g->getCurrentOtp($secret)])->json('recovery_codes');
    $challenge = fn () => $this->postJson('/api/auth/login', ['email' => 'tfa2@example.com', 'password' => 'secret123'])->json('challenge');

    // The same app code can't be used twice.
    $code = $g->getCurrentOtp($secret);
    $this->postJson('/api/auth/two-factor', ['challenge' => $challenge(), 'code' => $code])->assertOk();
    $this->postJson('/api/auth/two-factor', ['challenge' => $challenge(), 'code' => $code])->assertStatus(422);

    // Five wrong codes lock the account's second step, even for a correct code.
    \Illuminate\Support\Facades\Cache::flush(); // reset the per-IP limiter so the per-account lock is what's tested
    $c = $challenge();
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/auth/two-factor', ['challenge' => $c, 'code' => '000000'])->assertStatus(422);
    }
    $this->postJson('/api/auth/two-factor', ['challenge' => $c, 'code' => $old[0]])
        ->assertStatus(422)->assertJsonFragment(['code' => ['Too many wrong codes. Wait 15 minutes, then sign in again.']]);
    \Illuminate\Support\Facades\Cache::flush();

    // New recovery codes replace the old ones.
    $this->actingAs($user)->getJson('/api/me/two-factor')->assertJsonPath('recovery_codes_left', 8);
    $this->actingAs($user)->postJson('/api/me/two-factor/recovery-codes', ['password' => 'nope'])->assertStatus(422);
    $new = $this->actingAs($user)->postJson('/api/me/two-factor/recovery-codes', ['password' => 'secret123'])->assertOk()->json('recovery_codes');
    expect($new)->toHaveCount(8)->not->toContain($old[1]);
    $this->postJson('/api/auth/two-factor', ['challenge' => $challenge(), 'code' => $old[1]])->assertStatus(422);
    $this->postJson('/api/auth/two-factor', ['challenge' => $challenge(), 'code' => $new[0]])->assertOk();
    $this->actingAs($user->fresh())->getJson('/api/me/two-factor')->assertJsonPath('recovery_codes_left', 7);
});

it('never exposes the two-factor secret', function () {
    $user = User::factory()->create(['password' => 'secret123']);
    $this->actingAs($user)->postJson('/api/me/two-factor', ['password' => 'secret123'])->assertOk();

    $this->actingAs($user)->getJson('/api/me')->assertOk()
        ->assertJsonMissingPath('two_factor_secret')->assertJsonMissingPath('two_factor_recovery_codes');
});

it('records organiser actions in the activity log', function () {
    $user = User::factory()->create();
    $id = $this->actingAs($user)->postJson('/api/events', ['name' => 'Audit me'])->assertCreated()->json('id');
    $this->actingAs($user)->postJson("/api/events/{$id}/categories", ['name' => 'Captain'])->assertCreated();
    $this->actingAs($user)->patchJson("/api/events/{$id}", ['results_visibility' => 'live'])->assertOk();

    $actions = $this->actingAs($user)->getJson("/api/events/{$id}/activity")->assertOk()->json('*.action');
    expect($actions)->toContain('event.created')->toContain('category.created')->toContain('event.visibility');
});

it('erases voter contact details on request and automatically after the retention period', function () {
    $user = User::factory()->create();
    $event = Event::create(['user_id' => $user->id, 'name' => 'Old', 'link_token' => 'tok-old',
        'starts_at' => now()->subDays(60), 'ends_at' => now()->subDays(40)]);
    $recent = Event::create(['user_id' => $user->id, 'name' => 'Recent', 'link_token' => 'tok-new',
        'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(2)]);
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA2222', 'email' => 'a@example.com', 'phone' => '+2348000000001']);
    Voter::create(['event_id' => $recent->id, 'code' => 'BBBB2222', 'email' => 'b@example.com']);

    config(['ballotify.voter_contact_retention_days' => 30]);
    $this->artisan('ballotify:purge-voter-contacts')->assertSuccessful();

    expect(Voter::where('code', 'AAAA2222')->first()->email)->toBeNull()
        ->and(Voter::where('code', 'BBBB2222')->first()->email)->toBe('b@example.com');

    $this->actingAs($user)->postJson("/api/events/{$recent->id}/voters/erase-contacts")->assertOk()->assertJsonPath('erased', 1);
    expect(Voter::where('code', 'BBBB2222')->first()->email)->toBeNull();
});
