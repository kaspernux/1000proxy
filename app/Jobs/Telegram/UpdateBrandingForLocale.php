<?php

namespace App\Jobs\Telegram;

use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateBrandingForLocale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $locale;
    public array $only;

    public $tries = 5;
    public $backoff = [1, 2, 4, 8, 16];

    public function __construct(string $locale, array $only = ['name','short','description'])
    {
        $this->locale = $locale;
        $this->only = $only;
        $this->onQueue('telegram');
    }

    public function handle(TelegramBotService $service): void
    {
        // For English, update default first then language_code specific
        if ($this->locale === 'en') {
            $service->setDefaultBrandingFromLocale('en', $this->only);
            // Also set language_code=en explicitly for parity
            $service->setBrandingForLocale('en', null, null, null, $this->only);
            return;
        }
        $service->setBrandingForLocale($this->locale, null, null, null, $this->only);
    }
}
