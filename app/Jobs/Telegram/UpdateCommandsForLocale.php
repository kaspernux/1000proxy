<?php

namespace App\Jobs\Telegram;

use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCommandsForLocale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?string $locale;

    public $tries = 5;
    public $backoff = [1, 2, 4, 8, 16];

    public function __construct(?string $locale = null)
    {
        $this->locale = $locale;
        $this->onQueue('telegram');
    }

    public function handle(TelegramBotService $service): void
    {
        $service->setCommandsForLocale($this->locale);
    }
}
