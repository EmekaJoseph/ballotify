<?php

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs sign-ins, failed sign-ins and account security changes', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret123']);

    $this->postJson('/api/auth/login', ['email' => 'ada@example.com', 'password' => 'wrong-one'])->assertStatus(422);
    $this->postJson('/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'whatever'])->assertStatus(422);
    $this->postJson('/api/auth/login', ['email' => 'ada@example.com', 'password' => 'secret123'])->assertOk();

    expect(AccountLog::where('action', 'auth.login_failed')->count())->toBe(1) // unknown addresses aren't kept
        ->and(AccountLog::where('action', 'auth.login')->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(AccountLog::where('action', 'auth.login')->value('ip'))->not->toBeNull();

    $this->actingAs($user)->patchJson('/api/me', ['current_password' => 'secret123', 'password' => 'newsecret123'])->assertOk();
    expect(AccountLog::where('action', 'account.password')->where('user_id', $user->id)->exists())->toBeTrue();

    // Suspended accounts that try to sign in are recorded too.
    $user->forceFill(['suspended_at' => now()])->save();
    auth()->forgetGuards();
    $this->postJson('/api/auth/login', ['email' => 'ada@example.com', 'password' => 'newsecret123'])->assertStatus(422);
    expect(AccountLog::where('description', 'like', '%suspended%')->where('user_id', $user->id)->exists())->toBeTrue();

    // Nothing secret is ever stored.
    expect(AccountLog::pluck('description')->implode(' '))->not->toContain('secret123');
});

it('logs admin actions on accounts, and only admins can read the log', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Platform Admin']);
    $user = User::factory()->create(['email' => 'bola@example.com']);

    $this->actingAs($admin)->postJson("/api/admin/users/{$user->id}/suspend")->assertOk();
    $this->actingAs($admin)->postJson("/api/admin/users/{$user->id}/unsuspend")->assertOk();
    $this->actingAs($admin)->postJson("/api/admin/users/{$user->id}/pro", ['enabled' => true, 'tier' => 'unlimited'])->assertOk();
    expect(AccountLog::where('action', 'admin.plan')->value('description'))->toContain('Unlimited');

    $suspended = AccountLog::where('action', 'admin.suspended')->first();
    expect($suspended->actor_id)->toBe($admin->id)->and($suspended->user_id)->toBe($user->id)
        ->and(AccountLog::where('action', 'admin.restored')->exists())->toBeTrue();

    $this->actingAs($admin)->getJson('/api/admin/logs')->assertOk()
        ->assertJsonPath('data.0.actor.name', 'Platform Admin');
    $this->actingAs($admin)->getJson('/api/admin/logs?group=admin&search=bola')->assertOk()
        ->assertJsonPath('total', 3);
    $this->actingAs($user)->getJson('/api/admin/logs')->assertNotFound();
});

it('keeps the entry readable after the account is deleted', function () {
    $user = User::factory()->create(['email' => 'chidi@example.com', 'password' => 'secret123']);
    $this->actingAs($user)->deleteJson('/api/me', ['password' => 'secret123'])->assertOk();

    $entry = AccountLog::where('action', 'account.deleted')->first();
    expect($entry)->not->toBeNull()->and($entry->user_id)->toBeNull()->and($entry->user_email)->toBe('chidi@example.com');
});
