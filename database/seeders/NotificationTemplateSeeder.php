<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Lang;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'key' => 'order_ready',
                'name' => 'Order Ready (Telegram)',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "🎉 Your order is ready!\n\n" .
                    "Order ID: #:id\n" .
                    "Server: :server\n" .
                    "Status: :status\n\n" .
                    "Open your order: :url",
                'enabled' => true,
            ],
            [
                'key' => 'broadcast_generic',
                'name' => 'Broadcast (Telegram)',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "📢 Announcement\n\n:message\n\n—\n1000proxy Team",
                'enabled' => true,
            ],
            // New templates for broader coverage
            [
                'key' => 'welcome_guest',
                'name' => 'Welcome (Guest)',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "👋 Welcome! Explore plans and create your account.\n\nDashboard: :url",
                'enabled' => true,
            ],
            [
                'key' => 'welcome_customer',
                'name' => 'Welcome (Customer)',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "👋 Welcome back, :name!\n\nDashboard: :url",
                'enabled' => true,
            ],
            [
                'key' => 'welcome_staff',
                'name' => 'Welcome (Staff)',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "🛠 Admin Panel\n\nOpen admin: :url",
                'enabled' => true,
            ],
            [
                'key' => 'help',
                'name' => 'Help',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "🤖 Commands:\n/start, /menu, /help, /login, /balance, /topup, /myproxies, /plans, /orders, /buy, /config, /reset, /status, /support, /signup, /profile",
                'enabled' => true,
            ],
            [
                'key' => 'balance_summary',
                'name' => 'Balance Summary',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "💳 Wallet Balance: :amount",
                'enabled' => true,
            ],
            [
                'key' => 'no_plans',
                'name' => 'No Plans',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "❌ No plans available right now. Please check back later.",
                'enabled' => true,
            ],
            [
                'key' => 'buy_need_plan',
                'name' => 'Buy Needs Plan',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "🛒 Please specify a plan ID to buy. Use /plans to browse.",
                'enabled' => true,
            ],
            [
                'key' => 'plan_unavailable',
                'name' => 'Plan Unavailable',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "❌ That plan is not available.",
                'enabled' => true,
            ],
            [
                'key' => 'insufficient_balance',
                'name' => 'Insufficient Balance',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "💳 Insufficient balance. Top up here: :url",
                'enabled' => true,
            ],
            [
                'key' => 'purchase_requires_account',
                'name' => 'Purchase Requires Account',
                'channel' => 'telegram',
                'locale' => 'en',
                'subject' => null,
                'body' => "🆕 Please create an account to complete your purchase.",
                'enabled' => true,
            ],
        ];

        // Localized variants for supported locales
        $locales = ['ru','fr','es','pt','zh','ar','hi'];

        $t = function(string $key, string $locale, array $data = [], ?string $fallback = null) {
            $val = Lang::get($key, $data, $locale);
            if (is_string($val) && $val !== $key) return $val;
            if ($fallback) return $fallback;
            return Lang::get($key, $data, 'en');
        };

        foreach ($locales as $loc) {
            // order_ready localized
            $rows[] = [
                'key' => 'order_ready',
                'name' => 'Order Ready (Telegram) ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => '🎉 ' . $t('telegram.messages.order_ready_title', $loc) . "\n\n"
                    . $t('telegram.messages.order_id_line', $loc, ['id' => ':id']) . "\n"
                    . $t('telegram.messages.order_server_line', $loc, ['server' => ':server']) . "\n"
                    . $t('telegram.messages.order_status_line', $loc, ['status' => ':status']) . "\n\n"
                    . $t('telegram.messages.order_completed_block', $loc, ['url' => ':url']),
                'enabled' => true,
            ];

            // broadcast_generic localized (header/footer + :message)
            $rows[] = [
                'key' => 'broadcast_generic',
                'name' => 'Broadcast (Telegram) ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => '📢 ' . $t('telegram.messages.broadcast_title', $loc, [], 'Announcement') . "\n\n:message\n\n"
                    . $t('telegram.messages.broadcast_footer', $loc, [], "—\n1000proxy Team"),
                'enabled' => true,
            ];

            // welcome_guest localized using start_welcome (has :url)
            $rows[] = [
                'key' => 'welcome_guest',
                'name' => 'Welcome (Guest) ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => $t('telegram.messages.start_welcome', $loc, ['url' => ':url'], '👋 Welcome!'),
                'enabled' => true,
            ];

            // welcome_customer/staff fallback to English phrasing
            $rows[] = [
                'key' => 'welcome_customer',
                'name' => 'Welcome (Customer) ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => '👋 ' . $t('telegram.menu.title', $loc, [], 'Welcome') . ', :name!\n\n' . $t('telegram.common.open_dashboard', $loc, [], 'Open Dashboard') . ': :url',
                'enabled' => true,
            ];
            $rows[] = [
                'key' => 'welcome_staff',
                'name' => 'Welcome (Staff) ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => '🛠 ' . $t('telegram.admin.panel_title', $loc, [], 'Admin Panel') . "\n\n" . $t('telegram.common.open_dashboard', $loc, [], 'Open Dashboard') . ': :url',
                'enabled' => true,
            ];

            // help (compact, localized)
            $helpLines = [
                '🤖 ' . $t('telegram.help.title', $loc, [], 'Help'),
                '',
                '/start — ' . $t('telegram.help.start', $loc, [], 'Start'),
                '/balance — ' . $t('telegram.help.balance', $loc, [], 'Balance'),
                '/topup — ' . $t('telegram.help.topup', $loc, [], 'Top up'),
                '/signup — ' . $t('telegram.help.signup', $loc, [], 'Sign up'),
                '/profile — ' . $t('telegram.help.profile', $loc, [], 'Profile'),
                '/myproxies — ' . $t('telegram.help.myproxies', $loc, [], 'Services'),
                '/config [id] — ' . $t('telegram.help.config', $loc, [], 'Get config'),
                '/reset [id] — ' . $t('telegram.help.reset', $loc, [], 'Reset traffic'),
                '/status [id] — ' . $t('telegram.help.status', $loc, [], 'Status'),
                '/plans — ' . $t('telegram.help.plans', $loc, [], 'Plans'),
                '/orders — ' . $t('telegram.help.orders', $loc, [], 'Orders'),
                '/buy [id] — ' . $t('telegram.help.buy', $loc, [], 'Buy'),
                '/support — ' . $t('telegram.help.support', $loc, [], 'Support'),
                '/help — ' . $t('telegram.help.help', $loc, [], 'Help'),
            ];
            $rows[] = [
                'key' => 'help',
                'name' => 'Help ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => implode("\n", $helpLines),
                'enabled' => true,
            ];

            // simple message keys
            $rows[] = [
                'key' => 'balance_summary',
                'name' => 'Balance Summary ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => '💳 ' . $t('telegram.wallet.title', $loc, [], 'Wallet Balance') . ': :amount',
                'enabled' => true,
            ];
            $rows[] = [
                'key' => 'no_plans',
                'name' => 'No Plans ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => $t('telegram.messages.no_plans', $loc, [], 'No plans available right now. Please check back later.'),
                'enabled' => true,
            ];
            $rows[] = [
                'key' => 'buy_need_plan',
                'name' => 'Buy Needs Plan ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => $t('telegram.messages.buy_need_plan', $loc, [], 'Please specify a plan ID to buy. Use /plans to browse.'),
                'enabled' => true,
            ];
            $rows[] = [
                'key' => 'plan_unavailable',
                'name' => 'Plan Unavailable ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => $t('telegram.messages.plan_unavailable', $loc, [], 'That plan is not available.'),
                'enabled' => true,
            ];
            $rows[] = [
                'key' => 'insufficient_balance',
                'name' => 'Insufficient Balance ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => $t('telegram.messages.insufficient_balance', $loc, ['url' => ':url'], 'Insufficient balance. Top up here: :url'),
                'enabled' => true,
            ];
            $rows[] = [
                'key' => 'purchase_requires_account',
                'name' => 'Purchase Requires Account ['.$loc.']',
                'channel' => 'telegram',
                'locale' => $loc,
                'subject' => null,
                'body' => $t('telegram.messages.purchase_requires_account', $loc, [], 'Please create an account to complete your purchase.'),
                'enabled' => true,
            ];
        }

        foreach ($rows as $row) {
            NotificationTemplate::updateOrCreate(
                [ 'key' => $row['key'], 'channel' => $row['channel'], 'locale' => $row['locale'] ],
                $row
            );
        }
    }
}
