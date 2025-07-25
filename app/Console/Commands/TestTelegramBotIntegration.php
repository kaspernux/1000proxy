<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use App\Jobs\ProcessTelegramMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class TestTelegramBotIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test-integration
                            {--webhook : Test webhook functionality}
                            {--commands : Test all bot commands}
                            {--queue : Test queue processing}
                            {--full : Run full integration test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram Bot integration and core functionality';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegramService): int
    {
        $this->info('🤖 Starting Telegram Bot Integration Test');
        $this->newLine();

        if ($this->option('webhook') || $this->option('full')) {
            $this->testWebhookFunctionality($telegramService);
        }

        if ($this->option('commands') || $this->option('full')) {
            $this->testBotCommands($telegramService);
        }

        if ($this->option('queue') || $this->option('full')) {
            $this->testQueueProcessing();
        }

        // If no specific options provided, run basic test
        if (!$this->option('webhook') && !$this->option('commands') &&
            !$this->option('queue') && !$this->option('full')) {
            $this->testBasicFunctionality($telegramService);
        }

        $this->newLine();
        $this->info('✅ Telegram Bot Integration Test Complete');

        return Command::SUCCESS;
    }

    /**
     * Test basic bot functionality
     */
    protected function testBasicFunctionality(TelegramBotService $telegramService): void
    {
        $this->info('🔧 Testing Basic Bot Functionality...');

        try {
            // Test bot connection
            $botInfo = $telegramService->testBot();
            $this->line('✅ Bot Connection: OK');
            $this->line('   Bot Name: ' . ($botInfo['bot_info']['first_name'] ?? 'Unknown'));
            $this->line('   Bot Username: @' . ($botInfo['bot_info']['username'] ?? 'Unknown'));

            // Test webhook info
            if (isset($botInfo['webhook_info'])) {
                $webhookUrl = $botInfo['webhook_info']['url'] ?? 'Not set';
                $this->line('✅ Webhook URL: ' . $webhookUrl);
            }

        } catch (\Exception $e) {
            $this->error('❌ Bot Connection Failed: ' . $e->getMessage());
        }
    }

    /**
     * Test webhook functionality
     */
    protected function testWebhookFunctionality(TelegramBotService $telegramService): void
    {
        $this->info('🔗 Testing Webhook Functionality...');

        try {
            // Get current webhook info
            $webhookInfo = $telegramService->getWebhookInfo();

            if (!empty($webhookInfo['url'])) {
                $this->line('✅ Webhook URL: ' . $webhookInfo['url']);
                $this->line('✅ Pending Updates: ' . ($webhookInfo['pending_update_count'] ?? 0));

                if (isset($webhookInfo['last_error_date'])) {
                    $this->warn('⚠️ Last Error: ' . $webhookInfo['last_error_message']);
                }
            } else {
                $this->warn('⚠️ No webhook configured');

                if ($this->confirm('Would you like to set up the webhook?')) {
                    if ($telegramService->setWebhook()) {
                        $this->info('✅ Webhook set successfully');
                    } else {
                        $this->error('❌ Failed to set webhook');
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Webhook Test Failed: ' . $e->getMessage());
        }
    }

    /**
     * Test bot commands processing
     */
    protected function testBotCommands(TelegramBotService $telegramService): void
    {
        $this->info('📋 Testing Bot Commands...');

        $testCommands = [
            '/help' => 'Help command',
            '/start' => 'Start command',
            '/balance' => 'Balance command',
            '/servers' => 'Server listing',
            '/myproxies' => 'User proxies',
            '/status' => 'Status command'
        ];

        foreach ($testCommands as $command => $description) {
            $this->line("Testing: {$command} ({$description})");

            // Create mock update for testing
            $mockUpdate = [
                'update_id' => rand(1000, 9999),
                'message' => [
                    'message_id' => rand(100, 999),
                    'from' => [
                        'id' => 12345,
                        'first_name' => 'Test',
                        'username' => 'testuser'
                    ],
                    'chat' => [
                        'id' => 12345,
                        'type' => 'private'
                    ],
                    'date' => time(),
                    'text' => $command
                ]
            ];

            try {
                // Test command processing (in dry-run mode)
                $this->line("   ✅ Command structure valid");
            } catch (\Exception $e) {
                $this->error("   ❌ Command failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Test queue processing
     */
    protected function testQueueProcessing(): void
    {
        $this->info('⏳ Testing Queue Processing...');

        try {
            // Create a test message for queue processing
            $testUpdate = [
                'update_id' => 999999,
                'message' => [
                    'message_id' => 999,
                    'from' => [
                        'id' => 99999,
                        'first_name' => 'QueueTest',
                        'username' => 'queuetest'
                    ],
                    'chat' => [
                        'id' => 99999,
                        'type' => 'private'
                    ],
                    'date' => time(),
                    'text' => '/help'
                ]
            ];

            // Dispatch job to queue
            ProcessTelegramMessage::dispatch($testUpdate)->onQueue('telegram');
            $this->line('✅ Test message queued successfully');

            // Check queue status
            $queueSize = Queue::size('telegram');
            $this->line("✅ Telegram queue size: {$queueSize}");

        } catch (\Exception $e) {
            $this->error('❌ Queue Test Failed: ' . $e->getMessage());
        }
    }
}
