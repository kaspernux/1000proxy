<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Services\EnhancedMailService;
use App\Mail\OrderPlaced;
use App\Models\User;
use App\Models\Order;

class TestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test
                            {email : The email address to send test mail to}
                            {--driver=log : Mail driver to use}
                            {--provider= : Specific provider to test (gmail, mailtrap, resend, mailgun, postmark)}
                            {--type=basic : Type of test email (basic, welcome, order, payment-received, payment-failed, service-activated, expiring, admin, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test mail functionality with different drivers, providers, and email types';

    private EnhancedMailService $mailService;

    public function __construct(EnhancedMailService $mailService)
    {
        parent::__construct();
        $this->mailService = $mailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $driver = $this->option('driver');
        $provider = $this->option('provider');
        $type = $this->option('type');

        $this->info("🚀 Testing mail functionality...");
        $this->info("📧 Email: {$email}");
        $this->info("🔧 Driver: {$driver}");

        if ($provider) {
            $this->info("📨 Provider: {$provider}");
        }

        $this->info("📝 Email Type: {$type}");
        $this->newLine();

        // Configure mail driver temporarily if specified
        if ($driver !== config('mail.default')) {
            config(['mail.default' => $driver]);
            $this->info("✅ Mail driver changed to: {$driver}");
        }

        // Configure provider if specified
        if ($provider) {
            $this->configureProvider($provider);
        }

        // Display current configuration
        $this->displayMailConfiguration();

        // Send test emails based on type
        if ($type === 'all') {
            $this->sendAllTestEmails($email);
        } else {
            $this->sendSingleTestEmail($email, $type);
        }

        $this->newLine();
        $this->info("✅ Mail test completed!");

        // Display mail statistics
        $this->displayMailStatistics();
    }

    private function sendAllTestEmails(string $email): void
    {
        $types = ['basic', 'welcome', 'admin', 'order', 'payment-received', 'payment-failed'];

        $this->info("📬 Sending all email types...");
        $this->newLine();

        foreach ($types as $type) {
            $this->sendSingleTestEmail($email, $type, false);
            sleep(1); // Small delay between emails
        }
    }

    private function sendSingleTestEmail(string $email, string $type, bool $showHeader = true): void
    {
        if ($showHeader) {
            $this->info("📤 Sending {$type} email to {$email}...");
        } else {
            $this->line("📤 Sending {$type} email...");
        }

        try {
            $success = false;

            switch ($type) {
                case 'basic':
                    $success = $this->mailService->sendTestEmail($email, 'basic');
                    break;

                case 'welcome':
                    $success = $this->mailService->sendTestEmail($email, 'welcome');
                    break;

                case 'admin':
                    $success = $this->mailService->sendTestEmail($email, 'admin');
                    break;

                case 'order':
                    $success = $this->sendTestOrderEmail($email);
                    break;

                case 'payment-received':
                    $success = $this->sendTestPaymentReceivedEmail($email);
                    break;

                case 'payment-failed':
                    $success = $this->sendTestPaymentFailedEmail($email);
                    break;

                case 'service-activated':
                    $success = $this->sendTestServiceActivatedEmail($email);
                    break;

                case 'expiring':
                    $success = $this->sendTestExpiringEmail($email);
                    break;

                default:
                    $this->warn("⚠️  Email type '{$type}' not supported in test mode.");
                    return;
            }

            if ($success) {
                $this->info("   ✅ {$type} email sent successfully!");
            } else {
                $this->error("   ❌ {$type} email failed to send!");
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Error sending {$type} email: " . $e->getMessage());
        }
    }

    private function sendTestOrderEmail(string $email): bool
    {
        try {
            // Create test data
            $testUser = new User([
                'name' => 'Test User',
                'email' => $email,
                'id' => 999999,
                'created_at' => now()
            ]);

            $testOrder = new Order([
                'id' => 999999,
                'user_id' => 999999,
                'grand_total' => 29.99,
                'status' => 'completed',
                'created_at' => now(),
            ]);

            $testOrder->setRelation('user', $testUser);

            return $this->mailService->sendOrderPlacedEmail($testOrder);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendTestPaymentReceivedEmail(string $email): bool
    {
        try {
            $testUser = new User([
                'name' => 'Test User',
                'email' => $email,
                'id' => 999999,
                'created_at' => now()
            ]);

            $testOrder = new Order([
                'id' => 999999,
                'user_id' => 999999,
                'grand_total' => 29.99,
                'status' => 'paid',
                'created_at' => now(),
            ]);

            $testOrder->setRelation('user', $testUser);

            return $this->mailService->sendPaymentReceivedEmail($testOrder, 'Credit Card', 'TXN_999999');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendTestPaymentFailedEmail(string $email): bool
    {
        try {
            $testUser = new User([
                'name' => 'Test User',
                'email' => $email,
                'id' => 999999,
                'created_at' => now()
            ]);

            return $this->mailService->sendPaymentFailedEmail($testUser, 999999, 29.99, 'Insufficient funds');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendTestServiceActivatedEmail(string $email): bool
    {
        try {
            $testUser = new User([
                'name' => 'Test User',
                'email' => $email,
                'id' => 999999,
                'created_at' => now()
            ]);

            $testOrder = new Order([
                'id' => 999999,
                'user_id' => 999999,
                'grand_total' => 29.99,
                'status' => 'active',
                'created_at' => now(),
            ]);

            $testOrder->setRelation('user', $testUser);

            $serverDetails = [
                [
                    'server' => 'proxy-us-1.1000proxies.com',
                    'port' => '8080',
                    'username' => 'test_user',
                    'password' => 'test_pass123'
                ]
            ];

            return $this->mailService->sendServiceActivatedEmail($testOrder, $serverDetails);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function sendTestExpiringEmail(string $email): bool
    {
        try {
            $testUser = new User([
                'name' => 'Test User',
                'email' => $email,
                'id' => 999999,
                'created_at' => now()
            ]);

            $testOrder = new Order([
                'id' => 999999,
                'user_id' => 999999,
                'grand_total' => 29.99,
                'status' => 'active',
                'created_at' => now(),
                'expires_at' => now()->addDays(7)
            ]);

            $testOrder->setRelation('user', $testUser);

            return $this->mailService->sendServiceExpiringEmail($testOrder, 7);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function configureProvider(string $provider): void
    {
        $this->info("🔧 Configuring provider: {$provider}");

        switch ($provider) {
            case 'gmail':
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => 'smtp.gmail.com',
                    'mail.mailers.smtp.port' => 587,
                    'mail.mailers.smtp.encryption' => 'tls',
                ]);
                break;

            case 'mailtrap':
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => 'sandbox.smtp.mailtrap.io',
                    'mail.mailers.smtp.port' => 2525,
                    'mail.mailers.smtp.encryption' => 'tls',
                ]);
                break;

            case 'resend':
                config(['mail.default' => 'resend']);
                break;

            case 'mailgun':
                config(['mail.default' => 'mailgun']);
                break;

            case 'postmark':
                config(['mail.default' => 'postmark']);
                break;

            default:
                $this->warn("⚠️  Unknown provider: {$provider}");
        }
    }

    private function displayMailConfiguration(): void
    {
        $config = $this->mailService->checkMailConfiguration();

        $this->info("📋 Current Mail Configuration:");
        $this->table(
            ['Setting', 'Value'],
            [
                ['Driver', $config['config']['driver']],
                ['Status', $this->getStatusIcon($config['status']) . ' ' . $config['status']],
                ['Host', $config['config']['host'] ?? 'N/A'],
                ['Port', $config['config']['port'] ?? 'N/A'],
                ['Encryption', $config['config']['encryption'] ?? 'N/A'],
                ['From Address', $config['config']['from_address']],
                ['From Name', $config['config']['from_name']],
            ]
        );

        if (!empty($config['issues'])) {
            $this->warn("⚠️  Configuration Issues:");
            foreach ($config['issues'] as $issue) {
                $this->warn("   • {$issue}");
            }
        }

        $this->newLine();
    }

    private function displayMailStatistics(): void
    {
        $stats = $this->mailService->getEmailStats();

        $this->info("📊 Mail Statistics:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Sent Today', $stats['total_sent_today']],
                ['Sent This Week', $stats['total_sent_week']],
                ['Sent This Month', $stats['total_sent_month']],
                ['Failed Today', $stats['failed_today']],
                ['Queue Size', $stats['queue_size']],
                ['Last Sent', $stats['last_sent'] ?? 'N/A'],
            ]
        );
    }

    private function getStatusIcon(string $status): string
    {
        return match ($status) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            default => '❓'
        };
    }
}

This test email confirms that:
✅ Laravel mail configuration is correct
✅ Mail driver ({$driver}) is functional
✅ Email templates and routing work properly

Provider: " . ($provider ?: 'Default Laravel Configuration') . "
Test conducted at: " . now()->format('Y-m-d H:i:s T') . "

Your proxy service is ready to send:
- Order confirmations
- Account notifications
- Marketing emails
- System alerts

---
1000proxy Team
            ";

            Mail::raw($message, function ($mail) use ($email) {
                $mail->to($email)
                     ->subject('✅ 1000proxy Mail Test - SUCCESS!')
                     ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->info("✅ Test email sent successfully to {$email} using {$driver} driver!");

            if ($driver === 'log') {
                $this->info("📋 Check the log files in storage/logs/laravel.log to see the email content.");
            } else {
                $this->info("📧 Check your inbox at {$email} for the test email.");
            }

            $this->newLine();
            $this->info("🚀 Mail functionality is working perfectly!");

        } catch (\Exception $e) {
            $this->error("❌ Failed to send test email: " . $e->getMessage());
            $this->newLine();
            $this->error("💡 Troubleshooting tips:");
            $this->error("1. Check your .env mail configuration");
            $this->error("2. Verify SMTP credentials are correct");
            $this->error("3. Ensure firewall allows SMTP connections");
            $this->error("4. Check if 2FA is enabled (use app passwords)");
            return 1;
        }

        return 0;
    }
}
