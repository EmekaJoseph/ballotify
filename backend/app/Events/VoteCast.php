<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * "The tally changed" signal for live results. It deliberately carries no
 * ballot content: pages refetch the totals.
 *
 * - private-ballotify.admin.event.{id}: always, for the organiser's Results tab
 *   and Present board (the event's team only, see routes/channels.php).
 * - event.{id} (public): only while results are public, for the public results
 *   page, so hidden results never leak a "something changed".
 */
class VoteCast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $eventId, public bool $resultsPublic = false) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('ballotify.admin.event.' . $this->eventId)];
        if ($this->resultsPublic) {
            $channels[] = new Channel('event.' . $this->eventId);
        }
        return $channels;
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
