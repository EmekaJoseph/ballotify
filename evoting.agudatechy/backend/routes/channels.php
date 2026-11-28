<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/*
| Private channels (Pusher). The browser asks POST /api/broadcasting/auth with
| its Bearer token; these callbacks decide who may listen.
*/

// Live tally for the organiser's Results tab: the owner and co-organisers only.
Broadcast::channel('evoting.admin.event.{event}', function (User $user, Event $event) {
    return Gate::forUser($user)->allows('view', $event);
});
