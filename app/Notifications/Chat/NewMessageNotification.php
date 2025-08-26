<?php

namespace App\Notifications\Chat;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public int $messageId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = Message::with(['sender','conversation'])->find($this->messageId);
        if (!$message) {
            return ['type' => 'chat:new', 'message_id' => $this->messageId];
        }
        $senderName = method_exists($message->sender, 'getAttribute')
            ? ($message->sender->name ?? 'Unknown')
            : 'Unknown';
        $body = trim((string) ($message->body ?? ''));
        $snippet = mb_strimwidth($body, 0, 120, $body && mb_strlen($body) > 120 ? '…' : '');
        return [
            'type' => 'chat:new',
            'conversation_id' => $message->conversation_id,
            'message_id' => $message->id,
            'sender_name' => $senderName,
            'snippet' => $snippet,
            'sent_at' => optional($message->created_at)->toIso8601String(),
        ];
    }
}
