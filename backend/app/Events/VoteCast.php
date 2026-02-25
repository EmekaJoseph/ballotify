<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteCast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $eventId;
    public array $choices;
    public string $time;

    public function __construct(int $eventId, array $choices)
    {
        $this->eventId = $eventId;
        $this->choices = $choices;
        $this->time = now()->toISOString();
    }

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
        return [
            'event_id' => $this->eventId,
            'choices' => $this->choices,
            'time' => $this->time,
        ];
    }
}

