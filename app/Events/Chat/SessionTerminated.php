<?php

namespace App\Events\Chat;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class SessionTerminated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public int $conversationId) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('conversations.' . $this->conversationId);
    }

    public function broadcastAs(): string
    {
        return 'conversation.terminated';
    }
}
