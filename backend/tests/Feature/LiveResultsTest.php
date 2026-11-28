<?php

use App\Events\VoteCast;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets only the event team join its private live-results channel', function () {
    // Pusher signs channel auth locally; no network call is made.
    config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret', 'broadcasting.connections.pusher.app_id' => '1',
        'broadcasting.connections.pusher.options.cluster' => 'mt1']);
    require base_path('routes/channels.php');

    $owner = User::factory()->create();
    $event = Event::create(['user_id' => $owner->id, 'name' => 'Union election', 'link_token' => 'tok-live']);
    $body = ['socket_id' => '1234.5678', 'channel_name' => "private-ballotify.admin.event.{$event->id}"];

    $this->actingAs($owner)->postJson('/api/broadcasting/auth', $body)->assertOk()->assertJsonStructure(['auth']);
    $this->actingAs(User::factory()->create())->postJson('/api/broadcasting/auth', $body)->assertForbidden();
    auth()->forgetGuards();
    $this->postJson('/api/broadcasting/auth', $body)->assertUnauthorized();
});

it('signals the organisers on every vote, and the public only while results are public', function () {
    expect(collect((new VoteCast(7, false))->broadcastOn())->map->name->all())->toBe(['private-ballotify.admin.event.7']);
    expect(collect((new VoteCast(7, true))->broadcastOn())->map->name->all())->toBe(['private-ballotify.admin.event.7', 'event.7']);
});
