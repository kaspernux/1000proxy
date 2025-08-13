<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Api;

class TelegramBroadcastSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $adminChatId;
    public int $total;
    public int $sent;
    public int $failed;

    public function __construct(int $adminChatId, int $total, int $sent, int $failed)
    {
        $this->onQueue('telegram');
        $this->adminChatId = $adminChatId;
        $this->total = $total;
        $this->sent = $sent;
        $this->failed = $failed;
    }

    public function handle(): void
    {
        $api = new Api(config('services.telegram.bot_token'));
        $text = "✅ Broadcast finished\n\n" .
            "📤 Sent: {$this->sent}\n" .
            "❌ Failed: {$this->failed}\n" .
            "📊 Total: {$this->total}";
        try {
            $api->sendMessage(['chat_id' => $this->adminChatId, 'text' => $text]);
        } catch (\Throwable $e) {
            // swallow
        }
    }
}
