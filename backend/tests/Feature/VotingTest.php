<?php

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use App\Models\Vote;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Build an open event with one category of two candidates and one voter code. */
function makeElection(array $eventAttrs = [], string $mode = 'single'): array
{
    $user = User::factory()->create();
    $event = Event::create(array_merge([
        'user_id' => $user->id,
        'event_type_id' => EventType::where('key', $mode)->value('id'),
        'name' => 'Student Union',
        'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
    ], $eventAttrs));
    $category = Category::create(['event_id' => $event->id, 'name' => 'President']);
    $a = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => 'Ada']);
    $b = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => 'Bola']);
    $voter = Voter::create(['event_id' => $event->id, 'code' => 'ABCD2345']);

    return compact('user', 'event', 'category', 'a', 'b', 'voter');
}

function ballot(array $e, array $candidates, string $code = 'ABCD2345'): array
{
    return [
        'code' => $code,
        'choices' => array_map(fn ($c) => ['category_id' => $e['category']->id, 'candidate_id' => $c->id], $candidates),
    ];
}

it('records a valid vote and marks the code as used', function () {
    $e = makeElection();

    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a']]))
        ->assertOk();

    expect(Vote::count())->toBe(1)
        ->and($e['voter']->fresh()->used_at)->not->toBeNull();
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

    expect(Vote::count())->toBe(1);
});

it('refuses votes before the event starts and after it ends', function () {
    $upcoming = makeElection(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
    $this->postJson("/api/public/events/{$upcoming['event']->link_token}/vote", ballot($upcoming, [$upcoming['a']]))
        ->assertForbidden();

    Voter::query()->delete();
    $closed = makeElection(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
    $this->postJson("/api/public/events/{$closed['event']->link_token}/vote", ballot($closed, [$closed['a']]))
        ->assertForbidden();

    expect(Vote::count())->toBe(0);
});

it('allows only one candidate per category in single-choice events', function () {
    $e = makeElection();

    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a'], $e['b']]))
        ->assertStatus(422);
});

it('allows several candidates per category in multiple-choice events', function () {
    $e = makeElection([], 'multiple');

    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a'], $e['b']]))
        ->assertOk();

    expect(Vote::count())->toBe(2);
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

it('returns tallied results with the event id for live updates', function () {
    $e = makeElection();
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['b']]))->assertOk();

    $this->getJson("/api/public/events/{$e['event']->link_token}/results")
        ->assertOk()
        ->assertJsonPath('event.id', $e['event']->id)
        ->assertJsonPath('stats.total_votes', 1)
        ->assertJsonPath('categories.0.leader.name', 'Bola');
});

it('re-enabling a used code discards the old ballot', function () {
    $e = makeElection();
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['a']]))->assertOk();

    $this->actingAs($e['user'])
        ->patchJson("/api/events/{$e['event']->id}/voters/{$e['voter']->id}", ['used_at' => null])
        ->assertOk();

    expect(Vote::count())->toBe(0);
    $this->postJson("/api/public/events/{$e['event']->link_token}/vote", ballot($e, [$e['b']]))->assertOk();
    expect(Vote::count())->toBe(1);
});
