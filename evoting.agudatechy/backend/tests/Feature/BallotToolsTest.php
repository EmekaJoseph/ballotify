<?php

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** An open multiple-choice event: one category of three candidates, one code. */
function multiElection(?int $maxChoices = null): array
{
    $user = User::factory()->create();
    $event = Event::create([
        'user_id' => $user->id,
        'name' => 'Board',
        'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(),
        'results_visibility' => 'live',
    ]);
    $category = Category::create(['event_id' => $event->id, 'name' => 'Directors', 'max_choices' => $maxChoices]);
    $cands = collect(['Ada', 'Bola', 'Chidi'])->map(fn ($n) => Candidate::create([
        'event_id' => $event->id, 'category_id' => $category->id, 'name' => $n,
    ]));
    Voter::create(['event_id' => $event->id, 'code' => 'MULTI2345']);

    return compact('user', 'event', 'category', 'cands');
}

function picks(array $e, int $n): array
{
    return [
        'code' => 'MULTI2345',
        'choices' => $e['cands']->take($n)->map(fn ($c) => ['category_id' => $e['category']->id, 'candidate_id' => $c->id])->all(),
    ];
}

// ------------------------------------------------------------------ pick limits

it('enforces the per-category pick limit in multiple-choice events', function () {
    $e = multiElection(2);
    $token = $e['event']->link_token;

    $this->getJson("/api/public/events/{$token}")->assertOk()->assertJsonPath('categories.0.max_choices', 2);
    $this->postJson("/api/public/events/{$token}/vote", picks($e, 3))->assertStatus(422);
    expect(Voter::first()->used_at)->toBeNull();

    $this->postJson("/api/public/events/{$token}/vote", picks($e, 2))->assertOk();
    expect(Candidate::sum('votes'))->toBe(2);
});

it('allows any number of picks when no limit is set', function () {
    $e = multiElection();
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", picks($e, 3))->assertOk();
});

it('sets the pick limit on a category, and locks it once voting starts', function () {
    $e = multiElection();
    $url = "/api/events/{$e['event']->id}/categories/{$e['category']->id}";

    $this->actingAs($e['user'])->patchJson($url, ['max_choices' => 2])->assertOk()->assertJsonPath('max_choices', 2);
    $this->actingAs($e['user'])->patchJson($url, ['max_choices' => 0])->assertStatus(422);

    Voter::first()->update(['used_at' => now()]);
    $this->actingAs($e['user'])->patchJson($url, ['max_choices' => 3])->assertStatus(409);
    // Sending the unchanged value (e.g. with a rename) is fine.
    $this->actingAs($e['user'])->patchJson($url, ['name' => 'Board members', 'max_choices' => 2])->assertOk();
});

// ------------------------------------------------------------------ duplicate

it('duplicates an event ballot without voters, votes or dates', function () {
    $e = multiElection(2);
    $e['cands'][0]->update(['description' => 'Chair', 'votes' => 9]);
    Category::create(['event_id' => $e['event']->id, 'name' => 'Auditors']); // added later, sorts first by name

    $res = $this->actingAs($e['user'])->postJson("/api/events/{$e['event']->id}/duplicate")
        ->assertCreated()
        ->assertJsonPath('name', 'Board (copy)')
        ->assertJsonPath('starts_at', null)
        ->assertJsonPath('voting_mode', 'codes')
        ->assertJsonPath('categories_count', 2)
        ->assertJsonPath('candidates_count', 3)
        ->assertJsonPath('voters_count', 0);

    $copy = Event::find($res->json('id'));
    expect($copy->link_token)->not->toBe($e['event']->link_token)
        // Ballot order (creation order) is preserved, not alphabetical.
        ->and($copy->categories()->pluck('name')->all())->toBe(['Directors', 'Auditors'])
        ->and($copy->categories()->first()->max_choices)->toBe(2)
        ->and($copy->candidates()->where('name', 'Ada')->first()->description)->toBe('Chair')
        ->and((int) $copy->candidates()->sum('votes'))->toBe(0);
});

it('does not let anyone duplicate another organiser’s event', function () {
    $e = multiElection();
    $this->actingAs(User::factory()->create())->postJson("/api/events/{$e['event']->id}/duplicate")->assertNotFound();
});

// ------------------------------------------------------------------ candidate import

it('imports candidates, creating missing categories and skipping duplicates', function () {
    $user = User::factory()->create();
    $event = Event::create(['user_id' => $user->id, 'name' => 'Awards', 'link_token' => (string) Str::uuid()]);
    Category::create(['event_id' => $event->id, 'name' => 'Best Actor']);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/candidates/import", ['candidates' => [
        ['category' => 'best actor', 'name' => 'Tobi', 'description' => 'Lead in Ojuju'],
        ['category' => 'Best  Actress', 'name' => 'Ada'],
        ['category' => 'Best Actor', 'name' => 'tobi'],
    ]])->assertCreated()
        ->assertJsonPath('created', 2)
        ->assertJsonPath('categories_created', 1)
        ->assertJsonPath('skipped.0.row', 3);

    expect($event->categories()->pluck('name')->sort()->values()->all())->toBe(['Best Actor', 'Best Actress'])
        ->and(Candidate::where('name', 'Tobi')->value('description'))->toBe('Lead in Ojuju')
        ->and(AuditLog::where('action', 'candidate.imported')->exists())->toBeTrue();
});

it('rejects a candidate import with a missing name, naming the row', function () {
    $user = User::factory()->create();
    $event = Event::create(['user_id' => $user->id, 'name' => 'Awards', 'link_token' => (string) Str::uuid()]);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/candidates/import", ['candidates' => [
        ['category' => 'A', 'name' => 'Ok'],
        ['category' => 'A', 'name' => ''],
    ]])->assertStatus(422)->assertJsonValidationErrors('candidates.1.name');
    expect(Candidate::count())->toBe(0);
});

it('refuses a candidate import once the ballot is locked', function () {
    $e = multiElection();
    Voter::first()->update(['used_at' => now()]);
    $this->actingAs($e['user'])->postJson("/api/events/{$e['event']->id}/candidates/import", ['candidates' => [
        ['category' => 'Directors', 'name' => 'Late entry'],
    ]])->assertStatus(409);
});

// ------------------------------------------------------------------ platform admin

it('hides the admin area from non-admins', function () {
    $this->actingAs(User::factory()->create())->getJson('/api/admin/overview')->assertNotFound();
});

it('shows platform totals, organisers and events to an admin, without ballot data', function () {
    $e = multiElection();
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    $this->actingAs($admin)->getJson('/api/admin/overview')->assertOk()
        ->assertJsonPath('users', 2)->assertJsonPath('events', 1)->assertJsonPath('events_live', 1);

    $this->actingAs($admin)->getJson('/api/admin/users?search=' . urlencode($e['user']->email))->assertOk()
        ->assertJsonPath('total', 1)->assertJsonPath('data.0.events_count', 1);

    $events = $this->actingAs($admin)->getJson('/api/admin/events?status=active')->assertOk()
        ->assertJsonPath('total', 1)->assertJsonPath('data.0.user.email', $e['user']->email);
    expect($events->json('data.0'))->not->toHaveKey('link_token');

    $this->actingAs($admin)->getJson('/api/admin/events?status=completed')->assertOk()->assertJsonPath('total', 0);
});

it('suspends an organiser: signs them out, blocks sign-in and takes their ballots offline', function () {
    $e = multiElection();
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $e['user']->createToken('api');

    $this->actingAs($admin)->postJson("/api/admin/users/{$e['user']->id}/suspend")->assertOk();
    expect($e['user']->tokens()->count())->toBe(0);

    $this->postJson('/api/auth/login', ['email' => $e['user']->email, 'password' => 'password'])
        ->assertStatus(422)->assertJsonValidationErrors('email');
    $this->actingAs($e['user']->fresh())->getJson('/api/events')->assertForbidden()->assertJsonPath('suspended', true);
    $this->getJson("/api/public/events/{$e['event']->link_token}")->assertNotFound();
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", picks($e, 1))->assertNotFound();

    $this->actingAs($admin)->postJson("/api/admin/users/{$e['user']->id}/unsuspend")->assertOk();
    $this->getJson("/api/public/events/{$e['event']->link_token}")->assertOk();
});

it('does not let an admin suspend themselves or another admin', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $other = User::factory()->create();
    $other->forceFill(['is_admin' => true])->save();

    $this->actingAs($admin)->postJson("/api/admin/users/{$admin->id}/suspend")->assertStatus(422);
    $this->actingAs($admin)->postJson("/api/admin/users/{$other->id}/suspend")->assertStatus(422);
});

it('grants admin rights from the command line', function () {
    $user = User::factory()->create(['email' => 'owner@example.com']);
    $this->artisan('ballotify:make-admin', ['email' => 'owner@example.com'])->assertSuccessful();
    expect($user->fresh()->is_admin)->toBeTrue();
    $this->artisan('ballotify:make-admin', ['email' => 'owner@example.com', '--revoke' => true])->assertSuccessful();
    expect($user->fresh()->is_admin)->toBeFalse();
});
