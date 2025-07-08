<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramTestBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Telegram bot connectivity and functionality';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegramBotService)
    {
        $this->info('Testing Telegram bot...');
        
        try {
            // Test bot connectivity
            $botInfo = $telegramBotService->testBot();
            
            $this->info('✅ Bot connectivity test passed');
            $this->line('');
            $this->line('Bot Information:');
            $this->line('===============');
            $this->line('Username: @' . $botInfo['bot_info']['username']);
            $this->line('First name: ' . $botInfo['bot_info']['first_name']);
            $this->line('ID: ' . $botInfo['bot_info']['id']);
            $this->line('Can join groups: ' . ($botInfo['bot_info']['can_join_groups'] ? 'Yes' : 'No'));
            $this->line('Can read all group messages: ' . ($botInfo['bot_info']['can_read_all_group_messages'] ? 'Yes' : 'No'));
            $this->line('Supports inline queries: ' . ($botInfo['bot_info']['supports_inline_queries'] ? 'Yes' : 'No'));
            
            // Test webhook info
            $webhookInfo = $botInfo['webhook_info'];
            $this->line('');
            $this->line('Webhook Information:');
            $this->line('==================');
            $this->line('URL: ' . ($webhookInfo['url'] ?? 'Not set'));
            $this->line('Pending updates: ' . ($webhookInfo['pending_update_count'] ?? 0));
            
            if (isset($webhookInfo['last_error_date'])) {
                $this->line('');
                $this->line('⚠️  Last webhook error:');
                $this->line('Date: ' . date('Y-m-d H:i:s', $webhookInfo['last_error_date']));
                $this->line('Message: ' . ($webhookInfo['last_error_message'] ?? 'None'));
            }
            
            // Configuration check
            $this->line('');
            $this->line('Configuration Check:');
            $this->line('==================');
            $this->line('Bot token: ' . (config('services.telegram.bot_token') ? '✅ Set' : '❌ Not set'));
            $this->line('Webhook URL: ' . (config('services.telegram.webhook_url') ? '✅ Set' : '❌ Not set'));
            $this->line('Secret token: ' . (config('services.telegram.secret_token') ? '✅ Set' : '❌ Not set'));
            
            if (config('services.telegram.webhook_url')) {
                $this->line('Webhook URL value: ' . config('services.telegram.webhook_url'));
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Bot test failed: ' . $e->getMessage());
            $this->line('');
            $this->line('Troubleshooting:');
            $this->line('1. Check if TELEGRAM_BOT_TOKEN is set in .env');
            $this->line('2. Verify the bot token is correct');
            $this->line('3. Ensure your server can reach api.telegram.org');
            $this->line('4. Check if webhook URL is accessible from internet');
            
            return 1;
        }
    }
}
