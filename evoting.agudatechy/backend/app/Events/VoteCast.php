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
 * - private-evoting.admin.event.{id}: always, for the organiser's Results tab
 *   (only the event's owner and co-organisers can join, see routes/channels.php).
 * - evoting.event.{id} (public): only while results are public, for the
 *   public results page, so hidden results never leak a "something changed".
 *
 * Channel names are prefixed so this app can share a Pusher app with another.
 */
class VoteCast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $eventId, public bool $resultsPublic = false) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('evoting.admin.event.' . $this->eventId)];
        if ($this->resultsPublic) {
            $channels[] = new Channel('evoting.event.' . $this->eventId);
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
