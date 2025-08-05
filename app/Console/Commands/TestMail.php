<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EnhancedMailService;
use App\Models\User;
use App\Models\Order;

class TestMail extends Command
{
    protected $signature = 'mail:test
                            {email : The email address to send test mail to}
                            {--driver=log : Mail driver to use}
                            {--provider= : Specific provider to test (gmail, mailtrap, resend, mailgun, postmark)}
                            {--type=basic : Type of test email (basic, welcome, order, payment-received, payment-failed, service-activated, expiring, admin, all)}';

    protected $description = 'Test mail functionality with different drivers, providers, and email types';

    private EnhancedMailService $mailService;

    public function __construct(EnhancedMailService $mailService)
    {
        parent::__construct();
        $this->mailService = $mailService;
    }

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

        // Temporarily override mail driver
        if ($driver !== config('mail.default')) {
            config(['mail.default' => $driver]);
            $this->info("✅ Mail driver changed to: {$driver}");
        }

        // Configure provider if specified
        if ($provider) {
            $this->configureProvider($provider);
        }

        // Show current mail config
        $this->displayMailConfiguration();

        // Send test email(s)
        if ($type === 'all') {
            $this->sendAllTestEmails($email);
        } else {
            $this->sendSingleTestEmail($email, $type);
        }

        $this->newLine();
        $this->info("✅ Mail test completed!");

        $this->displayMailStatistics();
    }

    private function sendAllTestEmails(string $email): void
    {
        $types = ['basic', 'welcome', 'admin', 'order', 'payment-received', 'payment-failed', 'service-activated', 'expiring'];

        $this->info("📬 Sending all email types...");
        $this->newLine();

        foreach ($types as $type) {
            $this->sendSingleTestEmail($email, $type, false);
            sleep(1); // Prevent rate limiting
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
                case 'welcome':
                case 'admin':
                    $success = $this->mailService->sendTestEmail($email, $type);
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
                    $this->warn("⚠️  Email type '{$type}' is not supported.");
                    return;
            }

            $success
                ? $this->info("   ✅ {$type} email sent successfully!")
                : $this->error("   ❌ {$type} email failed to send!");
        } catch (\Exception $e) {
            $this->error("   ❌ Error sending {$type} email: " . $e->getMessage());
        }
    }

    private function sendTestOrderEmail(string $email): bool
    {
        $user = new User(['id' => 999999, 'name' => 'Test User', 'email' => $email]);
        $order = new Order(['id' => 999999, 'user_id' => $user->id, 'grand_amount' => 29.99, 'status' => 'completed', 'created_at' => now()]);
        $order->setRelation('user', $user);
        return $this->mailService->sendOrderPlacedEmail($order);
    }

    private function sendTestPaymentReceivedEmail(string $email): bool
    {
        $user = new User(['id' => 999999, 'name' => 'Test User', 'email' => $email]);
        $order = new Order(['id' => 999999, 'user_id' => $user->id, 'grand_amount' => 29.99, 'status' => 'paid', 'created_at' => now()]);
        $order->setRelation('user', $user);
        return $this->mailService->sendPaymentReceivedEmail($order, 'Credit Card', 'TXN_999999');
    }

    private function sendTestPaymentFailedEmail(string $email): bool
    {
        $user = new User(['id' => 999999, 'name' => 'Test User', 'email' => $email]);
        return $this->mailService->sendPaymentFailedEmail($user, 999999, 29.99, 'Insufficient funds');
    }

    private function sendTestServiceActivatedEmail(string $email): bool
    {
        $user = new User(['id' => 999999, 'name' => 'Test User', 'email' => $email]);
        $order = new Order(['id' => 999999, 'user_id' => $user->id, 'grand_amount' => 29.99, 'status' => 'active', 'created_at' => now()]);
        $order->setRelation('user', $user);

        $serverDetails = [[
            'server' => 'proxy-us-1.1000proxies.com',
            'port' => '8080',
            'username' => 'test_user',
            'password' => 'test_pass123'
        ]];

        return $this->mailService->sendServiceActivatedEmail($order, $serverDetails);
    }

    private function sendTestExpiringEmail(string $email): bool
    {
        $user = new User(['id' => 999999, 'name' => 'Test User', 'email' => $email]);
        $order = new Order([
            'id' => 999999,
            'user_id' => $user->id,
            'grand_amount' => 29.99,
            'status' => 'active',
            'created_at' => now(),
            'expires_at' => now()->addDays(7)
        ]);
        $order->setRelation('user', $user);
        return $this->mailService->sendServiceExpiringEmail($order, 7);
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
            case 'mailgun':
            case 'postmark':
                config(['mail.default' => $provider]);
                break;

            default:
                $this->warn("⚠️  Unknown provider: {$provider}");
                break;
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
                $this->line(" - {$issue}");
            }
        }
    }

    private function getStatusIcon(string $status): string
    {
        return match ($status) {
            'ok' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            default => 'ℹ️'
        };
    }

    private function displayMailStatistics(): void
    {
        if (method_exists($this->mailService, 'getStats')) {
            $stats = $this->mailService->getStats();
            $this->info("📈 Mail Service Stats:");
            $this->table(['Metric', 'Value'], collect($stats)->map(fn($val, $key) => [$key, $val])->toArray());
        }
    }
}
