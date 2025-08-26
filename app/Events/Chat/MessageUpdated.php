<?php

namespace App\Events\Chat;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $messageId) {}

    public function broadcastOn(): array
    {
        $message = Message::find($this->messageId);
        return [new PrivateChannel('conversations.' . $message->conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.updated';
    }
}
