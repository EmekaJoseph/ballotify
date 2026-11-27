<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets new accounts in, but creating events waits for the admin', function () {
    $this->postJson('/api/auth/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret123', 'terms' => true])
        ->assertCreated()->assertJsonPath('user.awaiting_approval', true);
    $ada = User::where('email', 'ada@example.com')->first();

    $this->actingAs($ada)->postJson('/api/events', ['name' => 'Union election'])
        ->assertForbidden()->assertJsonPath('awaiting_approval', true);
    expect(Event::count())->toBe(0);

    // The admin approves: unlimited from then on.
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->getJson('/api/admin/users?filter=awaiting')->assertOk()->assertJsonPath('data.0.email', 'ada@example.com');
    $this->actingAs($admin)->postJson("/api/admin/users/{$ada->id}/approval", ['approved' => true])->assertOk();

    $this->actingAs($ada->fresh())->getJson('/api/me')->assertJsonPath('awaiting_approval', false);
    $id = $this->actingAs($ada->fresh())->postJson('/api/events', ['name' => 'Union election'])
        ->assertCreated()->assertJsonPath('voting_mode', 'codes')->assertJsonPath('limits.max_voters', null)->json('id');

    // More voters than any old plan allowed.
    $this->actingAs($ada->fresh())->postJson("/api/events/{$id}/voters/generate", ['count' => 6000])->assertCreated();
    expect(Event::find($id)->voters()->count())->toBe(6000);

    // Withdrawing the approval stops new events (existing ones stay).
    $this->actingAs($admin)->postJson("/api/admin/users/{$ada->id}/approval", ['approved' => false])->assertOk();
    $this->actingAs($ada->fresh())->postJson('/api/events', ['name' => 'Another'])->assertForbidden();
    expect(Event::count())->toBe(1);

    // Only the admin approves.
    $this->actingAs($ada->fresh())->postJson("/api/admin/users/{$ada->id}/approval", ['approved' => true])->assertNotFound();
});

it('keeps to secret ballots: no ranked choice or motions, email only, no payments', function () {
    $owner = User::factory()->create();
    $id = $this->actingAs($owner)->postJson('/api/events', ['name' => 'Club'])->assertCreated()->json('id');

    $this->actingAs($owner)->postJson("/api/events/{$id}/categories", ['name' => 'President'])->assertCreated();
    $this->actingAs($owner)->postJson("/api/events/{$id}/categories", ['name' => 'Rank them', 'method' => 'ranked'])->assertStatus(422);
    $this->actingAs($owner)->postJson("/api/events/{$id}/categories", ['name' => 'Approve dues', 'method' => 'motion'])->assertStatus(422);
    expect(Category::where('event_id', $id)->count())->toBe(1);

    $owner->markEmailAsVerified();
    $this->actingAs($owner)->postJson("/api/events/{$id}/voters/invite", ['channel' => 'sms', 'audience' => 'pending'])->assertStatus(422);
    $this->actingAs($owner)->patchJson("/api/events/{$id}", ['reminder_channel' => 'sms'])->assertStatus(422);

    // The payment, USSD and plan endpoints don't exist.
    $this->actingAs($owner)->postJson("/api/events/{$id}/upgrade", ['tier' => 'large'])->assertNotFound();
    $this->postJson('/api/paystack/webhook')->assertNotFound();
    $this->getJson('/api/public/plans')->assertNotFound();
});

it('seeds the platform admin', function () {
    $this->seed();
    $admin = User::where('email', 'admin@agudatechy.com')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->is_admin)->toBeTrue()
        ->and($admin->awaiting_approval)->toBeFalse()
        ->and(\Illuminate\Support\Facades\Hash::check('password123#', $admin->password))->toBeTrue();
});

it('lets the organiser add and remove an event logo, shown on the public pages', function () {
    $owner = User::factory()->create();
    $event = Event::create(['user_id' => $owner->id, 'name' => 'Union election', 'link_token' => 'tok-logo']);

    $path = $this->actingAs($owner)->post("/api/events/{$event->id}/logo", [
        'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 200),
    ], ['Accept' => 'application/json'])->assertOk()->json('brand_logo_url');
    expect($path)->toContain('/branding/');
    $file = $event->fresh()->brand_logo_path;
    expect(file_exists(public_path($file)))->toBeTrue();

    $this->getJson("/api/public/events/{$event->link_token}")->assertOk()->assertJsonPath('brand.logo_url', $path);

    $this->actingAs($owner)->postJson("/api/events/{$event->id}/logo", ['remove_logo' => true])
        ->assertOk()->assertJsonPath('brand_logo_url', null);
    expect(file_exists(public_path($file)))->toBeFalse();

    // Not an image, and strangers can't change it.
    $this->actingAs($owner)->post("/api/events/{$event->id}/logo", [
        'logo' => \Illuminate\Http\UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertUnprocessable();
    $this->actingAs(User::factory()->create())->postJson("/api/events/{$event->id}/logo", ['remove_logo' => true])->assertNotFound();
});
