<?php

namespace App\Jobs;

use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTelegramMessage implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 2;
    public int $timeout = 30;

    private array $update;

    /**
     * Create a new job instance.
     */
    public function __construct(array $update)
    {
        $this->update = $update;
        $this->onQueue('telegram'); // Use dedicated telegram queue
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramBotService $telegramService): void
    {
        try {
            Log::info('Processing Telegram message via queue', [
                'update_id' => $this->update['update_id'] ?? null,
                'chat_id' => $this->update['message']['chat']['id'] ?? null
            ]);

            $telegramService->processUpdate($this->update);

            Log::info('Telegram message processed successfully', [
                'update_id' => $this->update['update_id'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process Telegram message', [
                'update_id' => $this->update['update_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Rethrow to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle failed job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Telegram message processing failed permanently', [
            'update_id' => $this->update['update_id'] ?? null,
            'error' => $exception->getMessage(),
            'update' => $this->update
        ]);

        // Optionally notify admins about failed messages
        // You could implement admin notification here
    }

    /**
     * Get unique ID for the job (prevents duplicate processing)
     */
    public function uniqueId(): string
    {
        return 'telegram_message_' . ($this->update['update_id'] ?? uniqid());
    }
}
