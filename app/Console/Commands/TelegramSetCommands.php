<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramBotService;

class TelegramSetCommands extends Command
{
    protected $signature = 'telegram:set-commands {--lang=}';
    protected $description = 'Set Telegram bot commands for users';

    public function handle(TelegramBotService $service): int
    {
        $this->info('Setting Telegram bot commands...');
        if ($lang = $this->option('lang')) {
            config(['services.telegram.language' => $lang]);
        }

        $ok = $service->setCommands();
        if ($ok) {
            $this->info('✅ Commands updated successfully.');
            return self::SUCCESS;
        }

        $this->error('❌ Failed to update commands. Check logs for details.');
        return self::FAILURE;
    }
}
