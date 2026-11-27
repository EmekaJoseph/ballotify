<?php

use App\Jobs\SendVoterInvitation;
use App\Mail\TeamInvitation;
use App\Models\BallotReceipt;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ballotify.billing.enabled' => true,
        'services.paystack.secret_key' => 'sk_test_secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
    ]);
});

function gfEvent(array $attrs = [], ?User $owner = null): Event
{
    $owner ??= User::factory()->create();
    return Event::create(array_merge([
        'user_id' => $owner->id,
        'name' => 'Awards',
        'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addDay(),
        'results_visibility' => 'live',
    ], $attrs));
}

function gfPro(Event $event): Event
{
    $event->forceFill(['plan' => 'pro'])->save();
    return $event->fresh();
}

function gfBallot(Event $event): array
{
    $category = Category::create(['event_id' => $event->id, 'name' => 'Best Voice']);
    $a = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => 'Ada']);
    $b = Candidate::create(['event_id' => $event->id, 'category_id' => $category->id, 'name' => 'Bola']);
    return [$category, $a, $b];
}

// ------------------------------------------------------------------ receipts

it('gives each counted ballot a receipt that can be checked, without linking it to the voter', function () {
    $event = gfEvent();
    [$category, $a] = gfBallot($event);
    Voter::create(['event_id' => $event->id, 'code' => 'CODE2345']);

    $receipt = $this->postJson("/api/public/events/{$event->link_token}/vote", [
        'code' => 'CODE2345', 'choices' => [['category_id' => $category->id, 'candidate_id' => $a->id]],
    ])->assertOk()->json('receipt');

    expect($receipt)->toMatch('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{2}$/');
    // Only a hash is stored, with no voter or candidate column.
    expect(array_keys(BallotReceipt::first()->getAttributes()))->toBe(['id', 'event_id', 'code_hash', 'created_at']);

    $this->postJson("/api/public/events/{$event->link_token}/receipt", ['receipt' => strtolower($receipt)])
        ->assertOk()->assertJsonPath('valid', true);
    $this->postJson("/api/public/events/{$event->link_token}/receipt", ['receipt' => 'ZZZZ-ZZZZ-ZZ'])
        ->assertNotFound()->assertJsonPath('valid', false);
});

// ------------------------------------------------------------------ pick limits, shuffle, templates

it('uses each category’s own pick limit (choose one by default)', function () {
    $event = gfEvent();
    [$category, $a, $b] = gfBallot($event);
    Voter::create(['event_id' => $event->id, 'code' => 'CODE2345']);
    $both = ['code' => 'CODE2345', 'choices' => [
        ['category_id' => $category->id, 'candidate_id' => $a->id],
        ['category_id' => $category->id, 'candidate_id' => $b->id],
    ]];

    $this->postJson("/api/public/events/{$event->link_token}/vote", $both)->assertStatus(422);
    $category->update(['max_choices' => null]);
    $this->postJson("/api/public/events/{$event->link_token}/vote", $both)->assertOk();
});

it('can show candidates in random order without exposing the setting', function () {
    $event = gfEvent();
    [$category] = gfBallot($event);
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}/categories/{$category->id}", ['shuffle_candidates' => true])
        ->assertOk()->assertJsonPath('shuffle_candidates', true);

    $ballot = $this->getJson("/api/public/events/{$event->link_token}")->assertOk();
    expect($ballot->json('categories.0'))->not->toHaveKey('shuffle_candidates')
        ->and($ballot->json('candidates'))->toHaveCount(2);
});

it('creates an event from a template', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/api/event-templates')->assertOk()->assertJsonFragment(['key' => 'association']);

    $this->actingAs($user)->postJson('/api/events', ['name' => 'Club elections', 'template' => 'association'])
        ->assertCreated()->assertJsonPath('categories_count', 4)->assertJsonPath('voting_mode', 'codes');

    // Church template: council (pick up to 5) and officers; no motions here.
    $church = $this->actingAs($user)->postJson('/api/events', ['name' => 'Parish elections', 'template' => 'church'])->assertCreated()->json('id');
    expect(Category::where('event_id', $church)->where('name', 'Church Council')->value('max_choices'))->toBe(5)
        ->and(Category::where('event_id', $church)->where('method', '!=', 'plurality')->count())->toBe(0);

    // Templates that are gone here (paid voting, motions).
    $this->actingAs($user)->postJson('/api/events', ['name' => 'Awards', 'template' => 'award_night'])->assertStatus(422);
});

// ------------------------------------------------------------------ co-organisers

it('gives each co-organiser role the right access', function () {
    Mail::fake();
    $event = gfPro(gfEvent());
    $viewer = User::factory()->create(['email' => 'viewer@example.com']);
    $helper = User::factory()->create(['email' => 'helper@example.com']);
    $stranger = User::factory()->create();
    $owner = $event->user;

    $this->actingAs($owner)->postJson("/api/events/{$event->id}/members", ['email' => 'viewer@example.com', 'role' => 'viewer'])
        ->assertCreated()->assertJsonPath('joined', true);
    $this->actingAs($owner)->postJson("/api/events/{$event->id}/members", ['email' => 'HELPER@example.com', 'role' => 'voters'])->assertCreated();
    Mail::assertSent(TeamInvitation::class, 2);

    // Shared events appear in the member's list with their role.
    $this->actingAs($viewer)->getJson('/api/events')->assertOk()
        ->assertJsonPath('0.id', $event->id)->assertJsonPath('0.my_role', 'viewer')->assertJsonPath('0.abilities', ['view']);

    // Viewer: read only.
    $this->actingAs($viewer)->getJson("/api/events/{$event->id}/results")->assertOk();
    $this->actingAs($viewer)->patchJson("/api/events/{$event->id}", ['name' => 'Hacked'])->assertForbidden();
    $this->actingAs($viewer)->getJson("/api/events/{$event->id}/voters")->assertForbidden();

    // Voter manager: voters yes, ballot no, delete no.
    $this->actingAs($helper)->postJson("/api/events/{$event->id}/voters/generate", ['count' => 2])->assertCreated();
    $this->actingAs($helper)->postJson("/api/events/{$event->id}/categories", ['name' => 'New'])->assertForbidden();
    $this->actingAs($helper)->deleteJson("/api/events/{$event->id}")->assertForbidden();

    // Outsiders can't even see it exists.
    $this->actingAs($stranger)->getJson("/api/events/{$event->id}")->assertNotFound();
});

it('links an invitation once the invitee confirms the same email', function () {
    Mail::fake();
    $event = gfPro(gfEvent());
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/members", ['email' => 'new@example.com', 'role' => 'editor'])
        ->assertCreated()->assertJsonPath('joined', false);

    $newcomer = User::factory()->unverified()->create(['email' => 'new@example.com']);
    EventMember::claimFor($newcomer);
    expect(EventMember::first()->user_id)->toBeNull(); // not confirmed yet

    $newcomer->markEmailAsVerified();
    EventMember::claimFor($newcomer);
    $this->actingAs($newcomer)->patchJson("/api/events/{$event->id}", ['name' => 'Renamed by editor'])->assertOk();
});

// ------------------------------------------------------------------ paid public voting

// ------------------------------------------------------------------ reminders, WhatsApp, branding

it('sends reminders only to voters who have not voted', function () {
    Bus::fake();
    $event = gfEvent();
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA1111', 'email' => 'a@example.com']);
    Voter::create(['event_id' => $event->id, 'code' => 'BBBB2222', 'email' => 'b@example.com', 'used_at' => now()]);
    $event->user->markEmailAsVerified();

    // Email only here: SMS is refused.
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/remind", ['channel' => 'sms'])->assertStatus(422);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/remind", ['channel' => 'email'])
        ->assertOk()->assertJsonPath('queued', 1);
    Bus::assertDispatchedAfterResponse(SendVoterInvitation::class, fn ($job) => $job->kind === 'reminder');
});

it('sends a scheduled reminder once, when it is due', function () {
    Bus::fake();
    $event = gfPro(gfEvent());
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA1111', 'email' => 'a@example.com']);

    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['reminder_at' => now()->addHour()->toIso8601String(), 'reminder_channel' => 'email'])
        ->assertOk();
    $this->actingAs($event->user)->patchJson("/api/events/{$event->id}", ['reminder_at' => now()->addDays(3)->toIso8601String()])
        ->assertStatus(422); // after voting closes

    $this->travel(61)->minutes();
    $this->artisan('ballotify:send-reminders')->assertSuccessful();
    $this->artisan('ballotify:send-reminders')->assertSuccessful();
    Bus::assertDispatchedAfterResponseTimes(SendVoterInvitation::class, 1);
    expect($event->fresh()->reminder_sent_at)->not->toBeNull();
});

it('records codes shared on WhatsApp as sent', function () {
    $event = gfEvent();
    $voter = Voter::create(['event_id' => $event->id, 'code' => 'AAAA1111', 'phone' => '+2348031234567']);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/{$voter->id}/shared", ['channel' => 'whatsapp'])
        ->assertOk()->assertJsonPath('invited_via', 'whatsapp');
    expect($voter->fresh()->invited_at)->not->toBeNull();
});

// ------------------------------------------------------------------ results timeline, receipts, admin

it('includes votes over time in the results', function () {
    $event = gfEvent();
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA1111', 'used_at' => now()->subMinutes(30)]);
    Voter::create(['event_id' => $event->id, 'code' => 'BBBB2222', 'used_at' => now()->subMinutes(5)]);

    $timeline = $this->actingAs($event->user)->getJson("/api/events/{$event->id}/results")->assertOk()->json('timeline');
    expect(array_sum(array_column($timeline['points'], 'votes')))->toBe(2)
        ->and($timeline['bucket_minutes'])->toBeGreaterThan(0);
});

