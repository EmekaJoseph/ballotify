<?php

use App\Jobs\SendVoterInvitation;
use App\Mail\VoterInvitation;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function openEvent(): array
{
    $user = User::factory()->create();
    $event = Event::create([
        'user_id' => $user->id, 'name' => 'Club Vote', 'link_token' => 'tok-inv',
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(),
    ]);
    return [$user, $event];
}

it('normalises phone numbers to international format', function () {
    config(['ballotify.default_country_code' => '234']);
    expect(PhoneNumber::normalize('0801 234 5678'))->toBe('+2348012345678')
        ->and(PhoneNumber::normalize('+44 (20) 7946-0958'))->toBe('+442079460958')
        ->and(PhoneNumber::normalize('2348012345678'))->toBe('+2348012345678')
        ->and(PhoneNumber::normalize('8098765432'))->toBe('+2348098765432') // from Excel: leading 0 dropped
        ->and(PhoneNumber::normalize('  '))->toBeNull()
        ->and(PhoneNumber::isValid('+2348012345678'))->toBeTrue()
        ->and(PhoneNumber::isValid('12'))->toBeFalse();
});

it('adds a voter with email and phone and generates the code', function () {
    config(['ballotify.default_country_code' => '234']);
    [$user, $event] = openEvent();

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters", [
        'name' => 'Ada Obi', 'email' => ' ADA@Example.com ', 'phone' => '0801 234 5678',
    ])->assertCreated()
        ->assertJsonPath('email', 'ada@example.com')
        ->assertJsonPath('phone', '+2348012345678')
        ->assertJsonStructure(['code']);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters", ['email' => 'not-an-email'])
        ->assertStatus(422)->assertJsonValidationErrors('email');
    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters", ['phone' => 'abc'])
        ->assertStatus(422)->assertJsonValidationErrors('phone');
});

it('imports voters with contacts and reports the bad row', function () {
    [$user, $event] = openEvent();

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/import", ['voters' => [
        ['name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '+2348012345678'],
        ['name' => 'Bola', 'email' => 'bad@', 'phone' => ''],
    ]])->assertStatus(422)->assertJsonValidationErrors('voters.1.email');
    expect(Voter::count())->toBe(0);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/import", ['voters' => [
        ['name' => 'Ada', 'email' => 'ada@example.com', 'phone' => '+2348012345678'],
        ['name' => 'Bola'],
    ]])->assertCreated()->assertJsonPath('voters.0.email', 'ada@example.com')->assertJsonCount(2, 'voters');
});

it('queues invitations only for reachable voters who have not voted', function () {
    config(['queue.default' => 'database']); // the queue-worker path
    Queue::fake();
    [$user, $event] = openEvent();
    $a = Voter::create(['event_id' => $event->id, 'code' => 'AAAA2222', 'email' => 'a@example.com']);
    Voter::create(['event_id' => $event->id, 'code' => 'BBBB2222', 'phone' => '+2348000000001']);
    Voter::create(['event_id' => $event->id, 'code' => 'CCCC2222']);                                   // no contact
    Voter::create(['event_id' => $event->id, 'code' => 'DDDD2222', 'email' => 'd@x.com', 'used_at' => now()]); // voted

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'email', 'audience' => 'pending'])
        ->assertOk()->assertJson(['queued' => 1, 'email' => 1]);

    // Email only: anything else is refused.
    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'both', 'audience' => 'pending'])
        ->assertStatus(422)->assertJsonValidationErrors('channel');

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'email', 'audience' => 'selected', 'voter_ids' => [$a->id]])
        ->assertOk()->assertJson(['queued' => 1]);

    Queue::assertPushed(SendVoterInvitation::class, 2);
});

it('refuses to send invitations after voting has closed', function () {
    [$user, $event] = openEvent();
    $event->update(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'email', 'audience' => 'pending'])
        ->assertStatus(422);
});

it('emails the code and voting link, and records the result', function () {
    Mail::fake();
    config(['app.frontend_url' => 'https://vote.example.com']);
    [, $event] = openEvent();
    $voter = Voter::create(['event_id' => $event->id, 'name' => 'Ada', 'code' => 'K7M2QX9P', 'email' => 'ada@example.com']);

    (new SendVoterInvitation($voter->id))->handle();

    Mail::assertSent(VoterInvitation::class, fn ($m) => $m->hasTo('ada@example.com') && $m->voteUrl === 'https://vote.example.com/vote/tok-inv');
    expect($voter->fresh()->invited_at)->not->toBeNull()
        ->and($voter->fresh()->invited_via)->toBe('email')
        ->and($voter->fresh()->invite_error)->toBeNull();
});

it('renders the invitation email', function () {
    [, $event] = openEvent();
    $voter = Voter::create(['event_id' => $event->id, 'name' => 'Ada', 'code' => 'K7M2QX9P']);

    $html = (new VoterInvitation($voter, $event, 'https://vote.example.com/vote/tok-inv'))->render();

    expect($html)->toContain('K7M2QX9P')->toContain('Hello Ada')->toContain('https://vote.example.com/vote/tok-inv');
});

it('reports messaging status to the dashboard', function () {
    $user = User::factory()->create();
    config(['mail.default' => 'log']);

    $this->actingAs($user)->getJson('/api/messaging/status')
        ->assertOk()->assertJsonPath('email.live', false)->assertJsonMissingPath('sms');
});

it('keeps working when the mailer fails (sync queue)', function () {
    config(['queue.default' => 'sync']);
    // A mailer that always throws, as with wrong SMTP credentials.
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP auth failed'));
    [$user, $event] = openEvent();
    $voter = Voter::create(['event_id' => $event->id, 'code' => 'K7M2QX9P', 'email' => 'a@example.com', 'phone' => '+2348012345678']);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'email', 'audience' => 'pending'])
        ->assertOk()->assertJson(['queued' => 1]);

    $fresh = $voter->fresh();
    expect($fresh->invited_at)->toBeNull()
        ->and($fresh->invite_error)->toContain('SMTP auth failed');

    // The rest of the app is unaffected: the voter can still vote with the code.
    $this->getJson("/api/events/{$event->id}/voters")->assertStatus(200);
});

it('records a job that dies in the worker', function () {
    [, $event] = openEvent();
    $voter = Voter::create(['event_id' => $event->id, 'code' => 'K7M2QX9P', 'email' => 'a@example.com']);

    (new SendVoterInvitation($voter->id))->failed(new RuntimeException('timed out'));

    expect($voter->fresh()->invite_error)->toContain('timed out');
});

it('sends without a queue worker: the request returns, then messages go out', function () {
    config(['queue.default' => 'sync']);
    Mail::fake();
    [$user, $event] = openEvent();
    $voter = Voter::create(['event_id' => $event->id, 'code' => 'K7M2QX9P', 'email' => 'a@example.com']);

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'email', 'audience' => 'pending'])
        ->assertOk()->assertJson(['queued' => 1, 'queue' => 'sync']);

    Mail::assertSent(VoterInvitation::class, 1);
    expect($voter->fresh()->invited_at)->not->toBeNull();
});
