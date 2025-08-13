<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class TelegramBroadcastChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $chatIds;
    public string $message;

    public function __construct(array $chatIds, string $message)
    {
        $this->onQueue('telegram');
        $this->chatIds = $chatIds;
        $this->message = $message;
    }

    public function handle(): void
    {
        $api = new Api(config('services.telegram.bot_token'));
        foreach ($this->chatIds as $chatId) {
            try {
                $api->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $this->message,
                    'parse_mode' => 'HTML',
                ]);
                usleep(100000); // 0.1s pacing
            } catch (\Throwable $e) {
                Log::warning('Broadcast send failed', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
            }
        }
    }
}
