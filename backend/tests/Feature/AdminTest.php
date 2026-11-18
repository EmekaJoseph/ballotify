<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('registers, logs in and returns the current user', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Jane Organiser', 'email' => 'jane@example.com', 'password' => 'secret123', 'terms' => true,
    ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email']]);

    $token = $this->postJson('/api/auth/login', ['email' => 'jane@example.com', 'password' => 'secret123'])
        ->assertOk()->json('token');

    $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('email', 'jane@example.com');
});

it('updates the profile and requires the current password to change it', function () {
    $user = User::factory()->create(['password' => 'oldpassword']);

    $this->actingAs($user)->patchJson('/api/me', ['name' => 'New Name', 'password' => 'newpassword1'])
        ->assertStatus(422);

    $this->actingAs($user)->patchJson('/api/me', [
        'name' => 'New Name', 'current_password' => 'oldpassword', 'password' => 'newpassword1',
    ])->assertOk()->assertJsonPath('name', 'New Name');

    $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'newpassword1'])->assertOk();
});

it('creates an event with status, mode and counts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/events', [
        'name' => 'Awards Night',
        'description' => 'Annual staff awards',
        'selection_mode' => 'multiple',
        'starts_at' => now()->subMinute()->toISOString(),
        'ends_at' => now()->addDay()->toISOString(),
    ])->assertCreated()
        ->assertJsonPath('name', 'Awards Night')
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('selection_mode', 'multiple')
        ->assertJsonPath('voters_count', 0);
});

it('rejects an end date before the start date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/events', [
        'name' => 'Bad dates', 'starts_at' => '2026-05-02', 'ends_at' => '2026-05-01',
    ])->assertStatus(422)->assertJsonValidationErrors('ends_at');
});

it('hides other organisers\' events', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $event = Event::create(['user_id' => $owner->id, 'name' => 'Private', 'link_token' => 'tok-1']);

    $this->actingAs($intruder)->getJson("/api/events/{$event->id}")->assertNotFound();
    $this->actingAs($intruder)->deleteJson("/api/events/{$event->id}")->assertNotFound();
    $this->actingAs($intruder)->getJson('/api/events')->assertOk()->assertJsonCount(0);
});

it('does not let a nested resource from another event be edited', function () {
    $user = User::factory()->create();
    $mine = Event::create(['user_id' => $user->id, 'name' => 'Mine', 'link_token' => 'tok-a']);
    $alsoMine = Event::create(['user_id' => $user->id, 'name' => 'Also mine', 'link_token' => 'tok-b']);
    $category = Category::create(['event_id' => $alsoMine->id, 'name' => 'Treasurer']);

    $this->actingAs($user)
        ->patchJson("/api/events/{$mine->id}/categories/{$category->id}", ['name' => 'Hijacked'])
        ->assertNotFound();
});

it('generates unique voter codes in bulk', function () {
    $user = User::factory()->create();
    $event = Event::create(['user_id' => $user->id, 'name' => 'Bulk', 'link_token' => 'tok-bulk']);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/generate", ['count' => 300])
        ->assertCreated()->assertJsonCount(300, 'voters');

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/import", ['names' => ['Ada', 'Bola', '']])
        ->assertCreated()->assertJsonCount(3, 'voters');

    expect(Voter::where('event_id', $event->id)->distinct()->count('code'))->toBe(303);
});

it('uploads, replaces and removes a candidate photo', function () {
    $user = User::factory()->create();
    $event = Event::create(['user_id' => $user->id, 'name' => 'Photos', 'link_token' => 'tok-photo']);
    $category = Category::create(['event_id' => $event->id, 'name' => 'Best Dressed']);

    $created = $this->actingAs($user)->post("/api/events/{$event->id}/candidates", [
        'category_id' => $category->id,
        'name' => 'Chidi',
        'image' => UploadedFile::fake()->image('chidi.png'),
    ], ['Accept' => 'application/json'])->assertCreated()->json();

    expect($created['image_url'])->toStartWith('http')
        ->and(File::exists(public_path($created['image_path'])))->toBeTrue();

    $updated = $this->actingAs($user)->post("/api/events/{$event->id}/candidates/{$created['id']}", [
        'image' => UploadedFile::fake()->image('new.jpg'),
    ], ['Accept' => 'application/json'])->assertOk()->json();

    expect(File::exists(public_path($created['image_path'])))->toBeFalse();

    $this->actingAs($user)->deleteJson("/api/events/{$event->id}/candidates/{$created['id']}")->assertNoContent();
    expect(File::exists(public_path($updated['image_path'])))->toBeFalse();
});

it('accepts a closing time without an opening time, and validates partial updates', function () {
    $user = User::factory()->create();

    $id = $this->actingAs($user)->postJson('/api/events', [
        'name' => 'Open now', 'ends_at' => now()->addDay()->toISOString(),
    ])->assertCreated()->assertJsonPath('status', 'active')->json('id');

    $this->actingAs($user)->patchJson("/api/events/{$id}", ['starts_at' => now()->addDays(2)->toISOString()])
        ->assertStatus(422)->assertJsonValidationErrors('ends_at');

    $this->actingAs($user)->patchJson("/api/events/{$id}", ['ends_at' => now()->subMinute()->toISOString()])
        ->assertOk()->assertJsonPath('status', 'completed');
});

it('rejects a duplicate candidate name in the same category with a validation error', function () {
    $user = User::factory()->create();
    $event = Event::create(['user_id' => $user->id, 'name' => 'Dupes', 'link_token' => 'tok-dupe']);
    $category = Category::create(['event_id' => $event->id, 'name' => 'Captain']);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/candidates", ['category_id' => $category->id, 'name' => 'Tobi'])
        ->assertCreated();
    $this->actingAs($user)->postJson("/api/events/{$event->id}/candidates", ['category_id' => $category->id, 'name' => 'Tobi'])
        ->assertStatus(422)->assertJsonValidationErrors('name');
});

it('does not include the full voter list in the event detail response', function () {
    $user = User::factory()->create();
    $event = Event::create(['user_id' => $user->id, 'name' => 'Lean', 'link_token' => 'tok-lean']);
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA2222']);

    $this->actingAs($user)->getJson("/api/events/{$event->id}")
        ->assertOk()->assertJsonMissingPath('voters')->assertJsonPath('voters_count', 1);
});

it('does not allow an organiser to change their sign-in email', function () {
    $user = User::factory()->create(['email' => 'owner@example.com']);

    $this->actingAs($user)->patchJson('/api/me', ['name' => 'Renamed', 'email' => 'other@example.com'])
        ->assertStatus(422)->assertJsonValidationErrors('email');

    expect($user->fresh()->email)->toBe('owner@example.com');

    $this->actingAs($user)->patchJson('/api/me', ['name' => 'Renamed'])
        ->assertOk()->assertJsonPath('name', 'Renamed')->assertJsonPath('email', 'owner@example.com');
});
