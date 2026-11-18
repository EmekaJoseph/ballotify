<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    /**
     * Only the organiser who created an event may manage it. Other users get a
     * 404 rather than a 403 so event ids cannot be probed.
     */
    public function manage(User $user, Event $event): Response
    {
        return $event->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
