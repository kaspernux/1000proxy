<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramWebhookInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get information about the current Telegram webhook';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegramBotService)
    {
        $this->info('Getting Telegram webhook information...');
        
        try {
            $webhookInfo = $telegramBotService->getWebhookInfo();
            
            $this->line('');
            $this->line('Webhook Information:');
            $this->line('==================');
            $this->line('URL: ' . ($webhookInfo['url'] ?? 'Not set'));
            $this->line('Has custom certificate: ' . ($webhookInfo['has_custom_certificate'] ? 'Yes' : 'No'));
            $this->line('Pending updates: ' . ($webhookInfo['pending_update_count'] ?? 0));
            $this->line('Max connections: ' . ($webhookInfo['max_connections'] ?? 0));
            
            if (isset($webhookInfo['allowed_updates'])) {
                $this->line('Allowed updates: ' . implode(', ', $webhookInfo['allowed_updates']));
            }
            
            if (isset($webhookInfo['last_error_date'])) {
                $this->line('');
                $this->line('Last Error Information:');
                $this->line('======================');
                $this->line('Date: ' . date('Y-m-d H:i:s', $webhookInfo['last_error_date']));
                $this->line('Message: ' . ($webhookInfo['last_error_message'] ?? 'None'));
            }
            
            // Test bot connectivity
            $this->line('');
            $this->line('Testing bot connectivity...');
            $botInfo = $telegramBotService->testBot();
            
            if ($botInfo) {
                $this->info('✅ Bot is connected and working');
                $this->line('Bot username: @' . $botInfo['bot_info']['username']);
                $this->line('Bot first name: ' . $botInfo['bot_info']['first_name']);
                $this->line('Bot ID: ' . $botInfo['bot_info']['id']);
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error getting webhook info: ' . $e->getMessage());
            return 1;
        }
    }
}
