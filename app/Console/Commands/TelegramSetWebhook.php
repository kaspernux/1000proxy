<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the webhook URL for the Telegram bot';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegramBotService)
    {
        $this->info('Setting up Telegram webhook...');
        
        // Check if required configuration is present
        if (!config('services.telegram.bot_token')) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env file');
            return 1;
        }
        
        if (!config('services.telegram.webhook_url')) {
            $this->error('TELEGRAM_WEBHOOK_URL is not set in .env file');
            return 1;
        }
        
        try {
            // Set the webhook
            $success = $telegramBotService->setWebhook();
            
            if ($success) {
                $this->info('✅ Webhook set successfully!');
                
                // Get webhook info to confirm
                $webhookInfo = $telegramBotService->getWebhookInfo();
                
                $this->line('');
                $this->line('Webhook Information:');
                $this->line('URL: ' . ($webhookInfo['url'] ?? 'Not set'));
                $this->line('Has custom certificate: ' . ($webhookInfo['has_custom_certificate'] ? 'Yes' : 'No'));
                $this->line('Pending updates: ' . ($webhookInfo['pending_update_count'] ?? 0));
                $this->line('Max connections: ' . ($webhookInfo['max_connections'] ?? 0));
                
                if (isset($webhookInfo['allowed_updates'])) {
                    $this->line('Allowed updates: ' . implode(', ', $webhookInfo['allowed_updates']));
                }
                
                if (isset($webhookInfo['last_error_date'])) {
                    $this->line('Last error: ' . date('Y-m-d H:i:s', $webhookInfo['last_error_date']));
                    $this->line('Error message: ' . ($webhookInfo['last_error_message'] ?? 'None'));
                }
                
                return 0;
            } else {
                $this->error('❌ Failed to set webhook');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error setting webhook: ' . $e->getMessage());
            return 1;
        }
    }
}
