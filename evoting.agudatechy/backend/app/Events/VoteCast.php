<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * "The tally changed" signal for live results pages. It deliberately carries no
 * ballot content: the page refetches the totals.
 */
class VoteCast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $eventId) {}

    public function broadcastOn(): array
    {
        return [new Channel('event.' . $this->eventId)];
    }

    public function broadcastAs(): string
    {
        return 'vote.cast';
    }

    public function broadcastWith(): array
    {
        return ['event_id' => $this->eventId];
    }
}
