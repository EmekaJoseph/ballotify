<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/*
| Private channels (Pusher / Reverb). The browser asks POST /api/broadcasting/auth
| with its Bearer token; these callbacks decide who may listen.
*/

// Live tally for the organiser's Results tab and Present board: the event's team only.
Broadcast::channel('ballotify.admin.event.{event}', function (User $user, Event $event) {
    return Gate::forUser($user)->allows('view', $event);
});
