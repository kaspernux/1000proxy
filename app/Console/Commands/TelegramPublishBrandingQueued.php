<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use App\Jobs\Telegram\UpdateBrandingForLocale;
use App\Jobs\Telegram\UpdateCommandsForLocale;

class TelegramPublishBrandingQueued extends Command
{
    protected $signature = 'telegram:publish-branding-queued {--locales=} {--only=} {--with-commands}';
    protected $description = 'Publish Telegram bot branding per-locale via queue to avoid timeouts; optionally update commands as well';

    public function handle(): int
    {
        $supported = (array) (config('locales.supported') ?? ['en']);
        $optLocales = $this->option('locales');
        $locales = $supported;
        if (is_string($optLocales) && $optLocales !== '') {
            $locales = array_values(array_filter(array_map('trim', explode(',', $optLocales))));
        }

        $onlyOpt = $this->option('only');
        $only = ['name','short','description'];
        if (is_string($onlyOpt) && $onlyOpt !== '') {
            $only = array_values(array_filter(array_map('trim', explode(',', $onlyOpt))));
        }

        // Dispatch branding jobs
        foreach ($locales as $lc) {
            UpdateBrandingForLocale::dispatch($lc, $only);
        }
        $this->info('Queued branding updates for locales: '.implode(', ', $locales));

        if ($this->option('with-commands')) {
            // Default scope first
            UpdateCommandsForLocale::dispatch(null);
            foreach ($locales as $lc) {
                if ($lc === 'en') continue; // default covers it; we also set en explicitly in branding job
                UpdateCommandsForLocale::dispatch($lc);
            }
            $this->info('Queued commands update for default and specified locales.');
        }

        $this->info('Done. Process queue to apply changes.');
        return self::SUCCESS;
    }
}
