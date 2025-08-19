<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Site configuration
            ['key' => 'site_name', 'value' => '1000proxy', 'description' => 'Site name'],
            ['key' => 'site_description', 'value' => 'Professional XUI-Based Proxy Client Sales Platform', 'description' => 'Site description'],
            ['key' => 'site_url', 'value' => 'https://1000proxy.io', 'description' => 'Site URL'],
            ['key' => 'site_email', 'value' => 'support@1000proxy.io', 'description' => 'Support email'],
            ['key' => 'site_phone', 'value' => '+1-800-1000-0101', 'description' => 'Support phone'],

            // Currency and pricing
            ['key' => 'default_currency', 'value' => 'USD', 'description' => 'Default currency'],
            ['key' => 'currency_symbol', 'value' => '$', 'description' => 'Currency symbol'],
            ['key' => 'tax_rate', 'value' => '0.00', 'description' => 'Tax rate percentage'],
            ['key' => 'minimum_deposit', 'value' => '10.00', 'description' => 'Minimum deposit amount'],
            ['key' => 'maximum_deposit', 'value' => '10000.00', 'description' => 'Maximum deposit amount'],

            // Payment settings
            ['key' => 'stripe_enabled', 'value' => 'true', 'description' => 'Enable Stripe payments'],
            ['key' => 'nowpayments_enabled', 'value' => 'true', 'description' => 'Enable NowPayments crypto'],
            ['key' => 'crypto_deposit_fee', 'value' => '2.5', 'description' => 'Crypto deposit fee percentage'],
            ['key' => 'stripe_deposit_fee', 'value' => '3.0', 'description' => 'Stripe deposit fee percentage'],

            // XUI settings
            ['key' => 'xui_auto_provision', 'value' => 'true', 'description' => 'Enable auto provisioning'],
            ['key' => 'xui_default_timeout', 'value' => '30', 'description' => 'Default API timeout in seconds'],
            ['key' => 'xui_retry_count', 'value' => '3', 'description' => 'API retry count'],
            ['key' => 'xui_health_check_interval', 'value' => '300', 'description' => 'Health check interval in seconds'],

            // Email settings
            ['key' => 'mail_notifications_enabled', 'value' => 'true', 'description' => 'Enable email notifications'],
            ['key' => 'order_confirmation_email', 'value' => 'true', 'description' => 'Send order confirmation emails'],
            ['key' => 'payment_confirmation_email', 'value' => 'true', 'description' => 'Send payment confirmation emails'],

            // Mobile app settings
            ['key' => 'mobile_app_enabled', 'value' => 'true', 'description' => 'Enable mobile app'],
            ['key' => 'mobile_app_version', 'value' => '1.0.0', 'description' => 'Current mobile app version'],
            ['key' => 'mobile_force_update', 'value' => 'false', 'description' => 'Force app update'],

            // Telegram bot settings
            ['key' => 'telegram_bot_enabled', 'value' => 'true', 'description' => 'Enable Telegram bot'],
            ['key' => 'telegram_notifications', 'value' => 'true', 'description' => 'Enable Telegram notifications'],

            // Security settings
            ['key' => 'max_login_attempts', 'value' => '5', 'description' => 'Maximum login attempts'],
            ['key' => 'account_lockout_duration', 'value' => '900', 'description' => 'Account lockout duration in seconds'],
            ['key' => 'session_timeout', 'value' => '7200', 'description' => 'Session timeout in seconds'],

            // Business settings
            ['key' => 'refund_policy_days', 'value' => '7', 'description' => 'Refund policy days'],
            ['key' => 'support_hours', 'value' => '24/7', 'description' => 'Support hours'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'description' => 'Enable maintenance mode'],

            // Advanced features
            ['key' => 'auto_scaling_enabled', 'value' => 'true', 'description' => 'Enable auto scaling'],
            ['key' => 'load_balancing_enabled', 'value' => 'true', 'description' => 'Enable load balancing'],
            ['key' => 'analytics_enabled', 'value' => 'true', 'description' => 'Enable analytics'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded successfully!');
    }
}
