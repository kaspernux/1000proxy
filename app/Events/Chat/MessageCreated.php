<?php

namespace App\Events\Chat;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
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
        return 'message.created';
    }

    public function __destruct()
    {
        // Dispatch database notifications for bell counters after broadcast
        try {
            $message = Message::with(['conversation.participants','sender'])->find($this->messageId);
            if (!$message) return;
            $senderKey = $message->sender_type . ':' . $message->sender_id;
            foreach ($message->conversation->participants as $p) {
                $key = $p->participant_type . ':' . $p->participant_id;
                if ($key === $senderKey) continue;
                $notifiable = $p->participant; // User or Customer model, both use Notifiable
                if (!$notifiable) continue;
                try { $notifiable->notify(new \App\Notifications\Chat\NewMessageNotification($message->id)); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            // Swallow to avoid interfering with broadcasting
        }
    }
}
