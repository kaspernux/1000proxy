<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\TelegramNotification;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public int $notificationId) {}

    public function handle(TelegramBotService $svc): void
    {
        $record = TelegramNotification::find($this->notificationId);
        if (!$record) { return; }

        $message = $record->message ?: '';
        if ($message === '') { return; }

        $sent = 0; $failed = 0;
        Customer::whereNotNull('telegram_chat_id')
            ->when($record->recipients === 'linked', fn($q) => $q->whereNotNull('telegram_username'))
            ->chunk(500, function($chunk) use (&$sent, &$failed, $svc, $message) {
                foreach ($chunk as $c) {
                    try {
                        $svc->sendDirectMessage((int) $c->telegram_chat_id, $message);
                        $sent++;
                        usleep(75000);
                    } catch (\Throwable $e) {
                        $failed++;
                    }
                }
            });

        $record->update(['status' => 'sent', 'sent_at' => now()]);
        Log::info('Broadcast completed', ['id' => $record->id, 'sent' => $sent, 'failed' => $failed]);
    }
}
