<?php

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Build an open event with one category of two candidates and one voter code. */
function makeElection(array $eventAttrs = [], string $mode = 'single'): array
{
    $user = User::factory()->create();
    $event = Event::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Student Union',
        'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'results_visibility' => 'live',
    ], $eventAttrs));
    // single = choose one (the default); multiple = no pick limit.
    $category = Category::create(['event_id' => $event->id, 'name' => 'President', 'max_choices' => $mode === 'multiple' ? null : 1]);
    $a = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => 'Ada']);
    $b = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => 'Bola']);
    $voter = Voter::create(['event_id' => $event->id, 'code' => 'ABCD2345', 'name' => 'Voter One']);

    return compact('user', 'event', 'category', 'a', 'b', 'voter');
}

function ballot(array $e, array $candidates, string $code = 'ABCD2345'): array
{
    return [
        'code' => $code,
        'choices' => array_map(fn ($c) => ['category_id' => $e['category']->id, 'candidate_id' => $c->id], $candidates),
    ];
}

function totalVotes(): int
{
    return (int) Candidate::sum('votes');
}

it('records a valid vote and marks the code as used', function () {
    $e = makeElection();

    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a']]))
        ->assertOk();

    expect($e['a']->fresh()->votes)->toBe(1)
        ->and(totalVotes())->toBe(1)
        ->and($e['voter']->fresh()->used_at)->not->toBeNull();
});

it('keeps the ballot secret: nothing links a vote to the voter', function () {
    $e = makeElection();
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['b']]))->assertOk();

    // There is no table of individual ballots at all.
    expect(Schema::hasTable('votes'))->toBeFalse();
    // The voter row only knows THAT they voted, not for whom.
    expect(array_keys($e['voter']->fresh()->getAttributes()))
        ->not->toContain('candidate_id')->not->toContain('choices');
});

it('accepts voter codes case-insensitively', function () {
    $e = makeElection();

    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a']], ' abcd2345 '))
        ->assertOk();
});

it('rejects a second ballot with the same code', function () {
    $e = makeElection();
    $url = "/api/public/events/{$e['event']->link_token}/vote";

    $this->postJson($url, ballot($e, [$e['a']]))->assertOk();
    $this->postJson($url, ballot($e, [$e['b']]))->assertStatus(422);

    expect(totalVotes())->toBe(1);
});

it('refuses votes before the event starts and after it ends', function () {
    $upcoming = makeElection(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
    $this->postJson("/api/public/events/{$upcoming['event']->link_token}/vote", ballot($upcoming, [$upcoming['a']]))
        ->assertForbidden();

    Voter::query()->delete();
    $closed = makeElection(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
    $this->postJson("/api/public/events/{$closed['event']->link_token}/vote", ballot($closed, [$closed['a']]))
        ->assertForbidden();

    expect(totalVotes())->toBe(0);
});

it('allows only one candidate per category in single-choice events', function () {
    $e = makeElection();

    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a'], $e['b']]))
        ->assertStatus(422);
});

it('allows several candidates per category in multiple-choice events', function () {
    $e = makeElection([], 'multiple');

    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a'], $e['b'], $e['a']]))
        ->assertOk();

    expect(totalVotes())->toBe(2); // the duplicate pick counts once
});

it('rejects candidates from another event', function () {
    $e = makeElection();
    $other = makeElection();

    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", [
        'code' => 'ABCD2345',
        'choices' => [['category_id' => $e['category']->id, 'candidate_id' => $other['a']->id]],
    ])->assertStatus(422);
});

it('hides the ballot of an event that is not open', function () {
    $e = makeElection(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);

    $this->getJson("/api/public/events/{$e['event']->link_token}")
        ->assertForbidden()
        ->assertJsonPath('status', 'upcoming')
        ->assertJsonMissingPath('categories');
});

it('never exposes vote totals on the ballot', function () {
    $e = makeElection();
    $e['a']->increment('votes', 5);

    $this->getJson("/api/public/events/{$e['event']->link_token}")
        ->assertOk()
        ->assertJsonMissingPath('candidates.0.votes');
});

it('returns tallied results when results are live', function () {
    $e = makeElection();
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['b']]))->assertOk();

    $this->getJson("/api/public/events/{$e['event']->link_token}/results")
        ->assertOk()
        ->assertJsonPath('event.id', $e['event']->id)
        ->assertJsonPath('stats.total_votes', 1)
        ->assertJsonPath('categories.0.leader.name', 'Bola')
        ->assertJsonPath('integrity.changes_during_voting', 0);
});

it('hides results until voting closes when set to after_close', function () {
    $e = makeElection(['results_visibility' => 'after_close']);

    $this->getJson("/api/public/events/{$e['event']->link_token}/results")
        ->assertForbidden()->assertJsonPath('visibility', 'after_close')->assertJsonMissingPath('categories');

    $e['event']->update(['ends_at' => now()->subMinute()]);
    $this->getJson("/api/public/events/{$e['event']->link_token}/results")->assertOk();
});

it('never publishes private results, but the organiser can always see them', function () {
    $e = makeElection(['results_visibility' => 'private', 'ends_at' => now()->subMinute(), 'starts_at' => now()->subDay()]);

    $this->getJson("/api/public/events/{$e['event']->link_token}/results")->assertForbidden();
    $this->actingAs($e['user'])->getJson("/api/events/{$e['event']->id}/results")->assertOk()->assertJsonStructure(['categories']);
});

it('does not allow a used code to be reset', function () {
    $e = makeElection();
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a']]))->assertOk();

    $this->actingAs($e['user'])
        ->patchJson("/api/events/{$e['event']->id}/voters/{$e['voter']->id}", ['used_at' => null])
        ->assertStatus(422)->assertJsonValidationErrors('used_at');

    expect($e['voter']->fresh()->used_at)->not->toBeNull();
});

it('returns the voter name when a code is validated, and nothing for an invalid code', function () {
    $e = makeElection();
    $e['voter']->update(['name' => 'Ada Obi']);
    $url = "/api/public/events/{$e['event']->link_token}/code/validate";

    $this->postJson($url, ['code' => 'abcd2345'])->assertOk()->assertJson(['valid' => true, 'name' => 'Ada Obi']);
    $this->postJson($url, ['code' => 'WRONG999'])->assertStatus(422)->assertJsonMissingPath('name');
});

it('locks the ballot structure once voting has started, but allows typo fixes', function () {
    $e = makeElection();
    $id = $e['event']->id;
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a']]))->assertOk();
    $as = $this->actingAs($e['user']);

    $as->postJson("/api/events/{$id}/categories", ['name' => 'Treasurer'])->assertStatus(409)->assertJsonPath('locked', true);
    $as->deleteJson("/api/events/{$id}/categories/{$e['category']->id}")->assertStatus(409);
    $as->postJson("/api/events/{$id}/candidates", ['category_id' => $e['category']->id, 'name' => 'Chidi'])->assertStatus(409);
    $as->deleteJson("/api/events/{$id}/candidates/{$e['a']->id}")->assertStatus(409);

    // Cosmetic fixes are allowed, and recorded.
    $as->patchJson("/api/events/{$id}/candidates/{$e['a']->id}", ['name' => 'Ada Obi'])->assertOk();
    $as->patchJson("/api/events/{$id}/categories/{$e['category']->id}", ['name' => 'Presidency'])->assertOk();
    // Settings changes are logged but are not ballot changes.
    $as->patchJson("/api/events/{$id}", ['results_visibility' => 'live'])->assertOk();

    $this->getJson("/api/public/events/{$e['event']->link_token}/results")
        ->assertJsonPath('integrity.changes_during_voting', 2);
});
