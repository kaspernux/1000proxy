<?php

namespace App\Jobs;

use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TelegramRepublishAll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    public function __construct()
    {
        $this->onQueue('telegram');
    }

    public function handle(TelegramBotService $service): void
    {
        try {
            // Set default English first
            $service->setCommands(null);
            // Localized branding (includes per-language variants with backoff pacing inside)
            $service->setBrandingLocalized();

            // Ensure per-locale commands once more explicitly in case locales expanded
            foreach ((array) config('locales.supported', ['en']) as $lc) {
                $service->setCommandsForLocale((string) $lc);
                usleep(300_000); // pacing
            }

            Log::info('Telegram republish all completed');
        } catch (\Throwable $e) {
            Log::error('Telegram republish all failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
