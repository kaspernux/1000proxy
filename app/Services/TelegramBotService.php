<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\ServerPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\ServerClient;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TelegramBotService
{
    protected $telegram;
    protected bool $enhanced = true;

    public function __construct()
    {
        $this->telegram = new Api(config('services.telegram.bot_token'));
        $this->enhanced = (bool) (config('services.telegram.enhanced') ?? env('TELEGRAM_BOT_ENHANCED', true));
    }

    /**
     * Toggle enhanced logic via controller/feature flag.
     */
    public function setEnhanced(bool $enabled): void
    {
        $this->enhanced = $enabled;
    }

    /**
     * Set bot commands for Telegram clients
     */
    public function setCommands(?array $commands = null): bool
    {
        try {
            $default = [
                ['command' => 'start', 'description' => 'Link your account and get started'],
                ['command' => 'menu', 'description' => 'Open the main menu'],
                ['command' => 'help', 'description' => 'Show help and available commands'],
                ['command' => 'balance', 'description' => 'Check your wallet balance'],
                ['command' => 'topup', 'description' => 'Top up your wallet'],
                ['command' => 'myproxies', 'description' => 'List your active services'],
                ['command' => 'plans', 'description' => 'Browse available plans'],
                ['command' => 'orders', 'description' => 'View your recent orders'],
                ['command' => 'buy', 'description' => 'Buy a plan by ID (e.g., /buy 1)'],
                ['command' => 'config', 'description' => 'Get config for a client (e.g., /config <id>)'],
                ['command' => 'reset', 'description' => 'Reset traffic for a client (e.g., /reset <id>)'],
                ['command' => 'status', 'description' => 'Account or service status'],
                ['command' => 'support', 'description' => 'Contact support'],
                ['command' => 'signup', 'description' => 'Create an account'],
                ['command' => 'profile', 'description' => 'View or update your profile'],
            ];

            $payload = [
                'commands' => $commands ?? $default,
            ];

            // Optionally set language-specific commands
            $lang = config('services.telegram.language') ?? env('TELEGRAM_LANGUAGE');
            if (!empty($lang)) {
                $payload['language_code'] = $lang;
            }

            $resp = $this->telegram->setMyCommands($payload);
            \Illuminate\Support\Facades\Log::info('Telegram commands set', ['ok' => (bool)($resp['ok'] ?? true)]);
            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to set Telegram commands', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Set bot branding (name, short description, and description)
     */
    public function setBranding(?string $name = null, ?string $shortDescription = null, ?string $description = null): bool
    {
        try {
            if ($name) {
                $this->botApi('setMyName', ['name' => $name]);
            }
            if ($shortDescription) {
                $this->botApi('setMyShortDescription', ['short_description' => $shortDescription]);
            }
            if ($description) {
                $this->botApi('setMyDescription', ['description' => $description]);
            }
            Log::info('Telegram bot branding updated');
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to set Telegram branding', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Configure chat menu button
     * type: 'commands' or 'web_app' (with text + url)
     */
    public function setMenuButton(string $type = 'commands', ?string $text = null, ?string $url = null): bool
    {
        try {
            $menu = ['type' => 'commands'];
            if ($type === 'web_app' && $text && $url) {
                $menu = [
                    'type' => 'web_app',
                    'text' => $text,
                    'web_app' => ['url' => $url]
                ];
            }
            $this->botApi('setChatMenuButton', ['menu_button' => $menu]);
            Log::info('Telegram menu button updated', ['type' => $type]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to set Telegram menu button', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Low-level Bot API helper using Laravel HTTP
     */
    protected function botApi(string $method, array $params): array
    {
        $token = (string) config('services.telegram.bot_token');
        $url = "https://api.telegram.org/bot{$token}/{$method}";
        $resp = Http::asJson()->post($url, $params);
        if (!$resp->ok() || !($resp->json('ok') ?? false)) {
            throw new \RuntimeException('Telegram API error: ' . ($resp->json('description') ?? $resp->body()));
        }
        return $resp->json();
    }

    /**
     * Set webhook for Telegram bot
     */
    public function setWebhook(): bool
    {
        try {
            $baseUrl = rtrim((string) config('services.telegram.webhook_url'), '/');
            $secret = (string) (config('services.telegram.secret_token') ?? '');

            // If a secret is configured, support path-based secret for compatibility
            $url = $baseUrl;
            if (!empty($secret)) {
                $url .= '/' . $secret;
            }

            $params = [
                'url' => $url,
                'allowed_updates' => ['message', 'callback_query']
            ];
            if (!empty($secret)) {
                // Also set Telegram's header-based secret for stronger verification
                $params['secret_token'] = $secret;
            }
            $response = $this->telegram->setWebhook($params);

            Log::info('Telegram webhook set successfully', ['response' => $response]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set Telegram webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array
    {
        try {
            $response = $this->telegram->getWebhookInfo();
            return $response->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get webhook info', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Remove webhook
     */
    public function removeWebhook(): bool
    {
        try {
            $response = $this->telegram->removeWebhook();
            Log::info('Telegram webhook removed successfully', ['response' => $response]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove Telegram webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Test bot functionality
     */
    public function testBot(): array
    {
        try {
            $me = $this->telegram->getMe();
            return [
                'bot_info' => $me->toArray(),
                'webhook_info' => $this->getWebhookInfo()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to test bot', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Process incoming webhook update
     */
    public function processUpdate(array $update): void
    {
        try {
            $update = new Update($update);

            if ($update->getMessage()) {
                $message = $update->getMessage();
                if ($message instanceof Message) {
                    $this->handleMessage($message);
                }
            }

            if ($update->getCallbackQuery()) {
                $this->handleCallbackQuery($update->getCallbackQuery());
            }
        } catch (\Exception $e) {
            Log::error('Error processing Telegram update', [
                'error' => $e->getMessage(),
                'update' => $update
            ]);
        }
    }

    /**
     * Handle incoming messages
     */
    protected function handleMessage(Message $message): void
    {
        $chatId = $message->getChat()->getId();
        $text = $message->getText();
        $userId = $message->getFrom()->getId();

        // Extract command and parameters
        $command = strtok($text, ' ');
        $params = trim(substr($text, strlen($command)));

        // Support compact commands like /config_123 or /reset_abc-uuid
        if (preg_match('/^\/(config|reset|status|buy)_(.+)$/', $text, $m)) {
            $compactCmd = '/' . $m[1];
            $compactParam = $m[2];
            $command = $compactCmd;
            $params = $compactParam;
        }

        switch ($command) {
            case '/start':
                $this->handleStart($chatId, $userId, $params);
                break;

            case '/menu':
                $this->handleMenu($chatId, $userId);
                break;

            case '/balance':
                $this->handleBalance($chatId, $userId);
                break;

            case '/myproxies':
                $this->handleMyProxies($chatId, $userId);
                break;

            case '/servers':
                // Backward compatible alias for plans listing (paginated)
                $this->handleServersPage($chatId, $userId, 1);
                break;

            case '/plans':
                $this->handleServersPage($chatId, $userId, 1);
                break;

            case '/orders':
                $this->handleOrders($chatId, $userId);
                break;

            case '/buy':
                $this->handleBuy($chatId, $userId, $params);
                break;

            case '/topup':
                $this->handleTopup($chatId, $userId);
                break;

            case '/config':
                $this->handleConfig($chatId, $userId, $params);
                break;

            case '/reset':
                $this->handleReset($chatId, $userId, $params);
                break;

            case '/status':
                $this->handleStatus($chatId, $userId, $params);
                break;

            case '/support':
                $this->handleSupport($chatId, $userId, $params);
                break;

            case '/help':
                $this->handleHelp($chatId);
                break;

            case '/signup':
                $this->handleSignup($chatId, $userId);
                break;

            case '/profile':
                $this->handleProfile($chatId, $userId);
                break;

            // Admin Commands
            case '/admin':
                $this->handleAdminPanel($chatId, $userId);
                break;

            case '/users':
                $this->handleAdminUsers($chatId, $userId, $params);
                break;

            case '/serverhealth':
                $this->handleServerHealth($chatId, $userId);
                break;

            case '/stats':
                $this->handleSystemStats($chatId, $userId);
                break;

            case '/broadcast':
                $this->handleBroadcast($chatId, $userId, $params);
                break;

        default:
                // Check if the text is a linking code
                if (preg_match('/^[A-Za-z0-9]{8}$/', $text)) {
            $this->handleLinkingCode($chatId, $userId, $text, $message);
                } else {
                    // Check ongoing flow state (e.g., signup/profile updates)
                    if ($this->handleOngoingFlow($chatId, $userId, $text)) {
                        // handled
                    } else {
                        $this->sendMessage($chatId, "Unknown command. Type /help to see available commands.");
                    }
                }
        }
    }

    /**
     * Handle /start command
     */
    protected function handleStart(int $chatId, int $userId, string $params): void
    {
        // If a customer is already linked, jump to main menu
        $customer = Customer::where('telegram_chat_id', $chatId)->first();
        if ($customer) {
            $this->handleMenu($chatId, $userId);
            return;
        }

        // Not linked: friendly onboarding with CTAs
        $message = "Welcome to 1000proxy! 🚀\n\n";
        $message .= "You can browse plans now, and create an account when you're ready.\n\n";
        $message .= "To link later from the website:\n";
        $message .= "1) Visit: " . config('app.url') . "\n";
        $message .= "2) Login or create an account\n";
        $message .= "3) Account settings → Link Telegram Account, then paste the code here.\n\n";

        $keyboard = [
            'inline_keyboard' => [
                [ ['text' => '🌐 Browse Plans', 'callback_data' => 'view_servers'] ],
                [ ['text' => '🆕 Create Account', 'callback_data' => 'signup_start'] ],
                [ ['text' => '❓ Help', 'callback_data' => 'open_support'] ],
            ]
        ];
        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle /myproxies command
     */
    protected function handleMyProxies(int $chatId, int $userId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $clients = $customer->clients()->latest()->take(10)->get();

        if ($clients->isEmpty()) {
            $this->sendMessage($chatId, "📭 No active services found.\n\nUse /plans to browse and purchase.");
            return;
        }

        $message = "🔐 Your Active Services\n\n";
        foreach ($clients as $client) {
            $planName = $client->plan->name ?? '—';
            $location = $client->inbound->server->country ?? $client->inbound->server->ip ?? '—';
            $status = $client->status ?? ($client->enable ? 'active' : 'inactive');
            $used = $this->formatTraffic(($client->remote_up ?? 0) + ($client->remote_down ?? 0));

            $message .= "� {$planName}\n";
            $message .= "📍 {$location}\n";
            $message .= "📊 Status: {$status}\n";
            $message .= "📈 Traffic: {$used}\n";
            $message .= "🔗 /config_{$client->id} - Get config\n";
            $message .= "🔄 /reset_{$client->id} - Reset traffic\n";
            $message .= "📅 Created: " . ($client->created_at?->format('M j, Y') ?? '—') . "\n\n";
        }

        $message .= "💡 Use /config_[client_id] to get configuration\n";
        $message .= "🔗 Full dashboard: " . config('app.url') . "/dashboard";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /topup command
     */
    protected function handleTopup(int $chatId, int $userId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        $wallet = $user->wallet;
        $currentBalance = $wallet ? $wallet->balance : 0;

        $message = "💳 Top Up Your Wallet\n\n";
        $message .= "💰 Current Balance: $" . number_format($currentBalance, 2) . "\n\n";
        $message .= "🔗 Visit: " . config('app.url') . "/wallet\n\n";
        $message .= "💡 Payment Methods Available:\n";
        $message .= "• 💳 Credit/Debit Cards (Stripe)\n";
        $message .= "• 🅿️ PayPal\n";
        $message .= "• ₿ Bitcoin (BTC)\n";
        $message .= "• 🔒 Monero (XMR)\n";
        $message .= "• ☀️ Solana (SOL)\n\n";
        $message .= "⚡ Crypto payments are processed instantly!\n";
        $message .= "💰 Minimum top-up: $5.00";

        // Create inline keyboard for quick top-up amounts
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '$10', 'url' => config('app.url') . '/wallet?amount=10'],
                    ['text' => '$25', 'url' => config('app.url') . '/wallet?amount=25'],
                    ['text' => '$50', 'url' => config('app.url') . '/wallet?amount=50']
                ],
                [
                    ['text' => '$100', 'url' => config('app.url') . '/wallet?amount=100'],
                    ['text' => 'Custom Amount', 'url' => config('app.url') . '/wallet']
                ]
            ]
        ];

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle /config command
     */
    protected function handleConfig(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $this->sendMessage($chatId, "Please specify a client or item ID.\n\nExample: /config 1a2b-uuid or /config 123\n\nUse /myproxies to see your active services.");
            return;
        }

        $textId = trim($params);

        // Try as ServerClient UUID first
        $client = ServerClient::where('customer_id', $customer->id)->find($textId);
        $orderItem = null;

        if (!$client && ctype_digit($textId)) {
            // Fallback as OrderItem ID
            $orderItem = OrderItem::whereHas('order', fn($q) => $q->where('customer_id', $customer->id))
                ->where('id', (int) $textId)
                ->first();
            $client = $orderItem?->serverClients()->first();
        }

        if (!$client) {
            $this->sendMessage($chatId, "❌ Configuration not found for the provided ID.");
            return;
        }

        $config = $client->getDownloadableConfig();
        $message = "🔐 Configuration\n\n";
        $message .= "� Plan: " . ($client->plan->name ?? '—') . "\n";
        $message .= "🌐 Server: " . ($client->inbound->server->ip ?? '—') . "\n\n";
        if (!empty($config['client_link'])) {
            $message .= "� Client Link: {$config['client_link']}\n";
        }
        if (!empty($config['subscription_link'])) {
            $message .= "� Subscription: {$config['subscription_link']}\n";
        }
        if (!empty($config['json_link'])) {
            $message .= "� JSON: {$config['json_link']}\n";
        }
        $message .= "\n� QR codes and full setup in dashboard:\n" . config('app.url') . "/dashboard";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /reset command
     */
    protected function handleReset(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $this->sendMessage($chatId, "Please specify a client ID.\n\nExample: /reset 1a2b-uuid\n\nUse /myproxies to see your active services.");
            return;
        }

        $client = ServerClient::where('customer_id', $customer->id)->find(trim($params));
        if (!$client) {
            $this->sendMessage($chatId, "❌ Client not found or not accessible.");
            return;
        }

        // Send confirmation keyboard
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Yes, Reset', 'callback_data' => "reset_confirm_{$client->id}"],
                    ['text' => '❌ Cancel', 'callback_data' => "reset_cancel_{$client->id}"]
                ]
            ]
        ];

        $message = "🔄 Reset Traffic Confirmation\n\n";
        $message .= "� Plan: " . ($client->plan->name ?? '—') . "\n";
        $message .= "🌐 Server: " . ($client->inbound->server->ip ?? '—') . "\n\n";
        $message .= "⚠️ This will:\n";
        $message .= "• Clear traffic statistics\n";
        $message .= "• Keep your credentials the same\n\n";
        $message .= "Proceed?";

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle /status command
     */
    protected function handleStatus(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $this->sendMessage($chatId, "Welcome back, {$user->name}! \ud83c\udf89\n\nYour account is already linked. Tap Menu or type /menu to explore.");
            $this->handleMenu($chatId, $userId);
            $activeServices = $customer->clients()->count();
            $wallet = $customer->wallet;
            $balance = $wallet ? $wallet->balance : 0;

            $message = "📊 Account Status\n\n";
            $message .= "👤 User: {$customer->name}\n";
            $message .= "💰 Balance: $" . number_format($balance, 2) . "\n";
            $message .= "🔐 Active Services: {$activeServices}\n";
            $message .= "📅 Member Since: {$customer->created_at->format('M j, Y')}\n\n";
            $message .= "🔗 Full Dashboard: " . config('app.url') . "/dashboard\n";
            $message .= "💡 Use /status [client_id] for specific service status";

            $this->sendMessage($chatId, $message);
            return;
        }

        // Show specific client status
        $client = ServerClient::where('customer_id', $customer->id)->find(trim($params));
        if (!$client) {
            $this->sendMessage($chatId, "❌ Service not found.\n\nUse /myproxies to see your services.");
            return;
        }

        $message = "📊 Service Status\n\n";
        $message .= "� Plan: " . ($client->plan->name ?? '—') . "\n";
        $message .= "🌐 Server: " . ($client->inbound->server->ip ?? '—') . "\n";
        $message .= "🔌 Connection: " . (($client->enable ?? false) ? 'Active' : 'Inactive') . "\n";
        $message .= "📈 Upload: " . $this->formatTraffic((int)($client->remote_up ?? 0)) . "\n";
        $message .= "📉 Download: " . $this->formatTraffic((int)($client->remote_down ?? 0)) . "\n";
        $message .= "📊 Total: " . $this->formatTraffic((int)($client->remote_up + $client->remote_down)) . "\n";
        $message .= "🔄 Resets: " . (int)($client->reset ?? 0) . "\n";
        $message .= "📅 Created: " . ($client->created_at?->format('M j, Y H:i') ?? '—') . "\n\n";
        $message .= "🔗 /config_{$client->id} - Get configuration\n";
        $message .= "🔄 /reset_{$client->id} - Reset traffic";

        $this->sendMessage($chatId, $message);
        $this->handleMenu($chatId, $userId);
    }

    /**
     * Show the main inline menu with quick actions
     */
    protected function handleMenu(int $chatId, int $userId): void
    {
        $buttons = [
            [
                ['text' => '🧰 My Services', 'callback_data' => 'view_myproxies'],
                ['text' => '🛒 Plans', 'callback_data' => 'view_servers']
            ],
            [
                ['text' => '💳 Wallet', 'callback_data' => 'refresh_balance'],
                ['text' => '📦 Orders', 'callback_data' => 'view_orders']
            ],
            [
                ['text' => '🆘 Support', 'callback_data' => 'open_support']
            ]
        ];

        // If linked staff, add admin row
        $staff = $this->getStaffUser($chatId, $userId);
        if ($staff && in_array($staff->role, ['admin', 'support_manager', 'sales_support'])) {
            $buttons[] = [ ['text' => '🛠 Admin', 'callback_data' => 'admin_panel'] ];
        }

        $keyboard = ['inline_keyboard' => $buttons];
        $this->sendMessageWithKeyboard($chatId, "✨ Main Menu\nPick an option:", $keyboard);
    }

    /**
     * Handle /balance command
     */
    protected function handleBalance(int $chatId, int $userId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $wallet = $customer->wallet;
        $balance = $wallet ? $wallet->balance : 0;

        $message = "💰 Your Wallet Balance\n\n";
        $message .= "Balance: $" . number_format($balance, 2) . "\n\n";
        $message .= "💡 Use /buy to purchase proxy services\n";
        $message .= "💳 Visit " . config('app.url') . "/wallet to top up your balance";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /servers command
     */
    protected function handlePlans(int $chatId, int $userId): void
    {
    $customer = Customer::where('telegram_chat_id', $chatId)->first();

        $plans = ServerPlan::where('is_active', true)
            ->where('in_stock', true)
            ->where('on_sale', true)
            ->with(['server','category','brand'])
            ->orderBy('popularity_score', 'desc')
            ->take(10)
            ->get();

        if ($plans->isEmpty()) {
            $this->sendMessage($chatId, "No plans available at the moment. Please try again later.");
            return;
        }

        $message = "🌐 Available Plans\n\n";

        foreach ($plans as $plan) {
            $loc = $plan->server->country ?? $plan->server->ip ?? '—';
            $duration = $plan->days ? ($plan->days . ' days') : 'Monthly';
            $data = $plan->data_limit_gb ? ($plan->data_limit_gb . ' GB') : ($plan->volume ? ($plan->volume . ' GB') : 'Unlimited');
            $protocol = $plan->protocol ?? $plan->server->type ?? '—';
            $message .= "� {$plan->name}\n";
            $message .= "� {$loc} • 🔧 {$protocol}\n";
            $message .= "�️ {$duration} • 📶 {$data}\n";
            $message .= "� $" . number_format((float)$plan->price, 2) . "\n";
            $message .= "� /buy_{$plan->id}\n\n";
        }

        if ($customer) {
            $message .= "💡 Use /buy_[plan_id] to purchase";
            $this->sendMessage($chatId, $message);
        } else {
            $message .= "🔐 To purchase, please create an account.";
            $keyboard = [
                'inline_keyboard' => [
                    [ ['text' => '🆕 Create Account', 'callback_data' => 'signup_start'] ],
                    [ ['text' => '📋 How to link later', 'callback_data' => 'open_support'] ],
                ]
            ];
            $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
        }
    }

    /**
     * Handle /orders command
     */
    protected function handleOrders(int $chatId, int $userId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $orders = $customer->orders()->latest()->take(5)->get();

        if ($orders->isEmpty()) {
            $this->sendMessage($chatId, "You have no orders yet. Use /servers to browse available servers.");
            return;
        }

        $message = "📋 Your Recent Orders\n\n";

        foreach ($orders as $order) {
            $statusIcon = $this->getOrderStatusIcon($order->order_status ?? 'new');
            $firstItem = $order->items()->with('serverPlan.server')->first();
            $serverLabel = $firstItem?->serverPlan?->server?->country ?? $firstItem?->serverPlan?->server?->ip ?? '—';
            $amount = $order->grand_amount ?? $order->total_amount ?? 0;
            $message .= "{$statusIcon} Order #{$order->id}\n";
            $message .= "🌐 Server: {$serverLabel}\n";
            $message .= "💰 Amount: $" . number_format((float)$amount, 2) . "\n";
            $message .= "📅 Date: {$order->created_at->format('M j, Y')}\n";
            $message .= "📊 Status: " . ($order->payment_status ?? '—') . " / " . ($order->order_status ?? '—') . "\n\n";
        }

        $message .= "🔗 Visit " . config('app.url') . "/orders for detailed order management";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /buy command
     */
    protected function handleBuy(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        // Extract plan ID from params (e.g., "buy_1" or "1")
        $planId = null;
        if (str_starts_with($params, 'buy_')) {
            $planId = (int) substr($params, 4);
        } elseif (ctype_digit($params)) {
            $planId = (int) $params;
        }

        if (!$planId) {
            $this->sendMessage($chatId, "Please specify a plan ID. Use /plans to see available plans.");
            return;
        }

        $plan = ServerPlan::with('server')->find($planId);
        if (!$plan || !$plan->isAvailable()) {
            $this->sendMessage($chatId, "Plan not found or unavailable. Use /plans to see available options.");
            return;
        }

        // Check wallet balance
        $wallet = $customer->wallet;
        $price = (float) $plan->getTotalPrice();
        if (!$wallet || $wallet->balance < $price) {
            $this->sendMessage($chatId, "Insufficient balance. Please top up your wallet at " . config('app.url') . "/wallet");
            return;
        }

        // Create order + item and mark as paid to trigger provisioning
        try {
            $paymentMethod = PaymentMethod::where('slug', 'wallet')->first();

            $order = Order::create([
                'customer_id' => $customer->id,
                'grand_amount' => $price,
                'currency' => 'usd',
                'payment_method' => $paymentMethod->id ?? null,
                'order_status' => 'new',
                'payment_status' => 'paid',
                'notes' => 'Telegram bot purchase',
            ]);

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'server_plan_id' => $plan->id,
                'quantity' => 1,
                'unit_amount' => $plan->price,
                'total_amount' => $plan->price,
            ]);

            // Debit wallet with transaction
            $customer->payFromWallet($price, 'Telegram Order #' . $order->id);

            // Trigger provisioning via existing pipeline (OrderPaid event already fires on paid)
            // Optional: ensure provisioning kicks if listener expects dispatchWithDependencies
            try {
                ProcessXuiOrder::dispatch($order);
            } catch (\Throwable $t) {
                // Listener may handle it; ignore here
            }

            $message = "✅ Order Created Successfully!\n\n";
            $message .= "📋 Order ID: #{$order->id}\n";
            $message .= "📦 Plan: {$plan->name}\n";
            $message .= "🌐 Server: " . ($plan->server->country ?? $plan->server->ip ?? '—') . "\n";
            $message .= "💰 Amount: $" . number_format($price, 2) . "\n";
            $message .= "📊 Status: Processing\n\n";
            $message .= "⏳ Your configuration will be ready shortly. We'll notify you here when it's complete.";

            $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Telegram bot order creation failed', [
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);

            $this->sendMessage($chatId, "Failed to create order. Please try again or contact support.");
        }
    }

    /**
     * Handle /support command
     */
    protected function handleSupport(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $message = "📞 Support Options\n\n";
            $message .= "🔗 Web Support: " . config('app.url') . "/support\n";
            $message .= "📧 Email: support@1000proxy.io\n";
            $message .= "📱 Telegram: Use /support [your message] to send a message\n\n";
            $message .= "💡 Example: /support I can't connect to my proxy";

            $this->sendMessage($chatId, $message);
            return;
        }

        // Create support ticket
        $ticket = [
            'customer_id' => $customer->id,
            'subject' => 'Telegram Support Request',
            'message' => $params,
            'source' => 'telegram',
            'telegram_chat_id' => $chatId
        ];

        // Here you would typically save to a support tickets table
        Log::info('Telegram support ticket created', $ticket);

        $message = "📩 Support Ticket Created\n\n";
        $message .= "Your message has been sent to our support team. We'll respond as soon as possible.\n\n";
        $message .= "📋 Ticket Details:\n";
    $message .= "👤 User: {$customer->name}\n";
        $message .= "📝 Message: {$params}\n\n";
        $message .= "💬 You can also visit " . config('app.url') . "/support for more options.";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /help command
     */
    protected function handleHelp(int $chatId): void
    {
        $message = "🤖 1000proxy Bot Commands\n\n";
        $message .= "👤 Account Management:\n";
        $message .= "/start - Initialize bot and link account\n";
        $message .= "/balance - Check wallet balance\n";
        $message .= "/topup - Top up wallet balance\n\n";
        $message .= "🔐 Proxy Management:\n";
        $message .= "/myproxies - List your active services\n";
        $message .= "/config [client_id] - Get configuration\n";
        $message .= "/reset [client_id] - Reset traffic with confirmation\n";
        $message .= "/status [client_id] - Check service status\n\n";
        $message .= "🌐 Server & Orders:\n";
        $message .= "/plans - Browse available plans\n";
        $message .= "/orders - View order history\n";
        $message .= "/buy [plan_id] - Purchase service\n\n";
        $message .= "🆘 Support:\n";
        $message .= "/support [message] - Contact support\n";
        $message .= "/help - Show this help message\n\n";
        $message .= "💡 Examples:\n";
        $message .= "• /buy 1 - Purchase plan with ID 1\n";
        $message .= "• /config 1a2b-uuid - Get config for client\n";
        $message .= "• /reset 1a2b-uuid - Reset traffic for client\n";
        $message .= "• /status 1a2b-uuid - Check status of client\n";
        $message .= "• /support Can't connect to proxy - Send support message\n\n";
        $message .= "🔗 Web Dashboard: " . config('app.url');

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle reset confirmation
     */
    protected function handleResetConfirm(int $chatId, int $userId, string $clientId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $client = ServerClient::where('customer_id', $customer->id)->find($clientId);
        if (!$client) {
            $this->sendMessage($chatId, "❌ Client not found or already reset.");
            return;
        }

        try {
            // Prefer local reset with sync; fallback to service
            $client->resetTraffic();
            $message = "✅ Traffic Reset Successfully!\n\n";
            $message .= "� Plan: " . ($client->plan->name ?? '—') . "\n";
            $message .= "🌐 Server: " . ($client->inbound->server->ip ?? '—') . "\n";
            $message .= "📊 Statistics cleared\n\n";
            $message .= "🔗 Config: /config_{$client->id}";

            $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Telegram bot client reset failed', [
                'client_id' => $clientId,
                'error' => $e->getMessage()
            ]);

            $this->sendMessage($chatId, "❌ Reset failed. Please try again or contact support.");
        }
    }

    /**
     * Handle servers pagination
     */
    protected function handleServersPage(int $chatId, int $userId, int $page): void
    {
        // Allow guests to browse; purchase will prompt signup if not linked
        $isLinked = Customer::where('telegram_chat_id', $chatId)->exists();

        $perPage = 5;
        $offset = ($page - 1) * $perPage;

        $plans = ServerPlan::where('is_active', true)
            ->where('in_stock', true)
            ->where('on_sale', true)
            ->with('server')
            ->orderBy('popularity_score', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $totalPlans = ServerPlan::where('is_active', true)->where('in_stock', true)->where('on_sale', true)->count();
        $totalPages = max(1, (int) ceil($totalPlans / $perPage));

        if ($plans->isEmpty()) {
            $this->sendMessage($chatId, "No plans found on page {$page}.");
            return;
        }

        $message = "🌐 Available Plans (Page {$page}/{$totalPages})\n\n";

        foreach ($plans as $plan) {
            $loc = $plan->server->country ?? $plan->server->ip ?? '—';
            $message .= "� {$plan->name}\n";
            $message .= "� {$loc}\n";
            $message .= "� $" . number_format((float)$plan->price, 2) . "\n\n";
        }

    // Create pagination keyboard
    $keyboard = ['inline_keyboard' => []];
        $buttons = [];

        if ($page > 1) {
            $buttons[] = ['text' => '◀️ Previous', 'callback_data' => "server_page_" . ($page - 1)];
        }

        if ($page < $totalPages) {
            $buttons[] = ['text' => 'Next ▶️', 'callback_data' => "server_page_" . ($page + 1)];
        }

        if (!empty($buttons)) {
            $keyboard['inline_keyboard'][] = $buttons;
        }

        // Add purchase buttons
        foreach ($plans as $plan) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🛒 Buy {$plan->name} - $" . number_format((float)$plan->price, 2),
                 'callback_data' => "buy_plan_{$plan->id}"]
            ];
        }

        // For guests, offer quick signup CTA
        if (!$isLinked) {
            $keyboard['inline_keyboard'][] = [
                ['text' => '🆕 Create Account', 'callback_data' => 'signup_start']
            ];
        }

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle buy confirmation
     */
    protected function handleBuyConfirm(int $chatId, int $userId, int $planId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $plan = ServerPlan::with('server')->find($planId);
        if (!$plan || !$plan->isAvailable()) {
            $this->sendMessage($chatId, "❌ Plan not available.");
            return;
        }

        $wallet = $customer->wallet;
        if (!$wallet || $wallet->balance < $plan->price) {
            $message = "❌ Insufficient balance.\n\n";
            $message .= "💰 Required: $" . number_format((float)$plan->price, 2) . "\n";
            $message .= "💳 Current: $" . number_format($wallet ? $wallet->balance : 0, 2) . "\n\n";
            $message .= "Top up your wallet, then come back to confirm.";

            $keyboard = [
                'inline_keyboard' => [
                    [ ['text' => '💳 Top up wallet', 'url' => config('app.url') . '/wallet'] ],
                    [ ['text' => '🔄 I topped up, refresh', 'callback_data' => 'refresh_balance'] ],
                ]
            ];

            $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
            return;
        }

        // Create confirmation keyboard
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Confirm Purchase', 'callback_data' => "confirm_buy_{$planId}"],
                    ['text' => '❌ Cancel', 'callback_data' => 'cancel_buy']
                ]
            ]
        ];

        $message = "🛒 Confirm Purchase\n\n";
        $message .= "📦 Plan: {$plan->name}\n";
        $message .= "🌐 Server: " . ($plan->server->country ?? $plan->server->ip ?? '—') . "\n";
        $message .= "💵 Price: $" . number_format((float)$plan->price, 2) . "\n";
        $message .= "💰 Your Balance: $" . number_format($wallet->balance, 2) . "\n";
        $message .= "💳 After Purchase: $" . number_format($wallet->balance - (float)$plan->price, 2) . "\n\n";
        $message .= "Proceed with purchase?";

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle admin panel
     */
    protected function handleAdminPanel(int $chatId, int $userId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
    if (!$user || !$this->isAdmin($user)) {
            $this->sendMessage($chatId, "❌ Access denied. Admin privileges required.");
            return;
        }

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👥 Users', 'callback_data' => 'user_stats'],
                    ['text' => '🌐 Servers', 'callback_data' => 'server_health']
                ],
                [
                    ['text' => '📊 Statistics', 'callback_data' => 'system_stats'],
                    ['text' => '📢 Broadcast', 'callback_data' => 'admin_broadcast']
                ],
                [
                    ['text' => '🔄 Refresh', 'callback_data' => 'admin_panel']
                ]
            ]
        ];

        $message = "🔧 Admin Panel\n\n";
        $message .= "Choose an option to manage the system:";

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle admin users command
     */
    protected function handleAdminUsers(int $chatId, int $userId, string $params): void
    {
        $staff = $this->getStaffUser($chatId, $userId);
        if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, "❌ Access denied. Admin privileges required.");
            return;
        }

        if (empty($params)) {
            // Show user statistics
            $totalUsers = Customer::count();
            $activeUsers = Customer::whereHas('clients')->count();
            $telegramUsers = Customer::whereNotNull('telegram_chat_id')->count();
            $recentUsers = Customer::where('created_at', '>=', now()->subDays(7))->count();

            $message = "👥 User Statistics\n\n";
            $message .= "📊 Total Users: {$totalUsers}\n";
            $message .= "✅ Active Users: {$activeUsers}\n";
            $message .= "📱 Telegram Linked: {$telegramUsers}\n";
            $message .= "🆕 New (7 days): {$recentUsers}\n\n";
            $message .= "💡 Use /users [email] to search for specific user";

            $this->sendMessage($chatId, $message);
            return;
        }

        // Search for specific customer
        $searchUser = Customer::where('email', 'like', "%{$params}%")
            ->orWhere('name', 'like', "%{$params}%")
            ->first();

        if (!$searchUser) {
            $this->sendMessage($chatId, "❌ User not found: {$params}");
            return;
        }

    $orders = $searchUser->orders()->count();
    $activeOrders = $searchUser->clients()->count();
    $wallet = $searchUser->wallet;
        $balance = $wallet ? $wallet->balance : 0;

        $message = "👤 User Details\n\n";
        $message .= "📧 Email: {$searchUser->email}\n";
        $message .= "👤 Name: {$searchUser->name}\n";
        $message .= "💰 Balance: $" . number_format($balance, 2) . "\n";
        $message .= "📋 Total Orders: {$orders}\n";
        $message .= "✅ Active Proxies: {$activeOrders}\n";
    $message .= "📱 Telegram: " . ($searchUser->telegram_chat_id ? 'Linked' : 'Not Linked') . "\n";
        $message .= "📅 Joined: {$searchUser->created_at->format('M j, Y')}\n";
    $message .= "🔄 Last Login: " . ($searchUser->last_login_at ? $searchUser->last_login_at->format('M j, Y H:i') : 'Never');

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle server health
     */
    protected function handleServerHealth(int $chatId, int $userId): void
    {
    $staff = $this->getStaffUser($chatId, $userId);
    if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, "❌ Access denied. Admin privileges required.");
            return;
        }

        $servers = Server::with(['brand', 'category'])->get();
        $totalServers = $servers->count();
        $activeServers = $servers->where('status', 'active')->count();
        $inactiveServers = $servers->where('status', 'inactive')->count();

        $message = "🌐 Server Health Report\n\n";
        $message .= "📊 Total Servers: {$totalServers}\n";
        $message .= "✅ Active: {$activeServers}\n";
        $message .= "❌ Inactive: {$inactiveServers}\n\n";

        $message .= "🔍 Server Details:\n";
        foreach ($servers->take(10) as $server) {
            $statusIcon = $server->status === 'active' ? '✅' : '❌';
            $loadColor = $server->load > 80 ? '🔴' : ($server->load > 60 ? '🟡' : '🟢');

            $message .= "{$statusIcon} {$server->location}\n";
            $message .= "   {$loadColor} Load: " . ($server->load ?? 0) . "%\n";
            $message .= "   💰 Price: $" . number_format($server->price, 2) . "\n\n";
        }

        if ($totalServers > 10) {
            $message .= "... and " . ($totalServers - 10) . " more servers\n\n";
        }

        $message .= "🔗 Full details available in admin dashboard";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle user stats (for callback)
     */
    protected function handleUserStats(int $chatId, int $userId): void
    {
        $this->handleAdminUsers($chatId, $userId, '');
    }

    /**
     * Handle system stats
     */
    protected function handleSystemStats(int $chatId, int $userId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user || !$this->isAdmin($user)) {
            $this->sendMessage($chatId, "❌ Access denied. Admin privileges required.");
            return;
        }

        // System statistics
        $totalUsers = Customer::count();
        $totalOrders = Order::count();
        $completedOrders = Order::where('order_status', 'completed')->count();
        $pendingOrders = Order::where('order_status', 'processing')->count();
        $totalRevenue = (float) (Order::where('payment_status', 'paid')->sum('grand_amount'));
        $todayRevenue = (float) (Order::where('payment_status', 'paid')
            ->whereDate('created_at', today())
            ->sum('grand_amount'));
        $todayOrders = Order::whereDate('created_at', today())->count();

        $message = "📊 System Statistics\n\n";
        $message .= "👥 Total Users: {$totalUsers}\n";
        $message .= "📋 Total Orders: {$totalOrders}\n";
        $message .= "✅ Completed: {$completedOrders}\n";
        $message .= "⏳ Pending: {$pendingOrders}\n\n";
        $message .= "💰 Total Revenue: $" . number_format($totalRevenue, 2) . "\n";
        $message .= "📅 Today's Revenue: $" . number_format($todayRevenue, 2) . "\n";
        $message .= "📋 Today's Orders: {$todayOrders}\n\n";

        // Server statistics
    $totalServers = \App\Models\Server::count();
    $activeServers = \App\Models\Server::where('status', 'up')->count();
    $avgLoad = \App\Models\Server::where('status', 'up')->avg('load') ?? 0;

        $message .= "🌐 Server Stats:\n";
        $message .= "   Total: {$totalServers}\n";
        $message .= "   Active: {$activeServers}\n";
        $message .= "   Avg Load: " . number_format($avgLoad, 1) . "%\n\n";

        $message .= "🔄 Last Updated: " . now()->format('H:i:s');

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle broadcast message
     */
    protected function handleBroadcast(int $chatId, int $userId, string $params): void
    {
        $staff = $this->getStaffUser($chatId, $userId);
        if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, "❌ Access denied. Admin privileges required.");
            return;
        }

        if (empty($params)) {
            $message = "📢 Broadcast Message\n\n";
            $message .= "Send a message to all Telegram users:\n\n";
            $message .= "Usage: /broadcast [your message]\n\n";
            $message .= "Example:\n";
            $message .= "/broadcast Important maintenance scheduled for tonight at 2 AM UTC. All services will be temporarily unavailable.";

            $this->sendMessage($chatId, $message);
            return;
        }

        // Get all users with Telegram linked
    $telegramUsers = Customer::whereNotNull('telegram_chat_id')->get();
        $sentCount = 0;
        $failedCount = 0;

        $broadcastMessage = "📢 System Announcement\n\n{$params}\n\n";
        $broadcastMessage .= "—\n1000proxy Team";

        foreach ($telegramUsers as $telegramUser) {
            try {
                $this->sendMessage($telegramUser->telegram_chat_id, $broadcastMessage);
                $sentCount++;

                // Add small delay to avoid rate limiting
                usleep(100000); // 0.1 second
            } catch (\Exception $e) {
                $failedCount++;
                Log::warning('Broadcast message failed', [
                    'user_id' => $telegramUser->id,
                    'telegram_chat_id' => $telegramUser->telegram_chat_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $resultMessage = "✅ Broadcast Complete\n\n";
        $resultMessage .= "📤 Sent: {$sentCount}\n";
        $resultMessage .= "❌ Failed: {$failedCount}\n";
        $resultMessage .= "📊 Total Users: " . $telegramUsers->count();

        $this->sendMessage($chatId, $resultMessage);

        Log::info('Admin broadcast sent', [
            'admin_user_id' => $staff->id,
            'message' => $params,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount
        ]);
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(User $user): bool
    {
        return $user->is_admin || $user->hasRole('admin') || in_array($user->email, config('app.admin_emails', []));
    }

    /**
     * Get authenticated customer (end-user)
     */
    protected function getAuthenticatedCustomer(int $chatId, int $userId): ?Customer
    {
        $customer = Customer::where('telegram_chat_id', $chatId)->first();

        if (!$customer) {
            $this->sendMessage($chatId, "❌ Please link your account first.\n\nVisit your account settings at " . config('app.url') . " and link your Telegram account.");
            return null;
        }

        return $customer;
    }

    /**
     * Get authenticated staff user (for admin commands)
     */
    protected function getStaffUser(int $chatId, int $userId): ?User
    {
        return User::where('telegram_chat_id', $chatId)->first();
    }

    /**
     * Handle linking code
     */
    protected function handleLinkingCode(int $chatId, int $userId, string $code, Message $message): void
    {
        // Check if the code exists in cache
        $cacheKey = "telegram_linking_{$code}";
        $webUserId = cache()->get($cacheKey);

        if (!$webUserId) {
            $this->sendMessage($chatId, "❌ Invalid or expired linking code. Please generate a new one from your account settings.");
            return;
        }

        // Get customer from database
        $customer = Customer::find($webUserId);
        if (!$customer) {
            $this->sendMessage($chatId, "❌ User not found. Please try again.");
            return;
        }

        // Check if this Telegram account is already linked to another user
        $existingCustomer = Customer::where('telegram_chat_id', $chatId)->first();
        if ($existingCustomer && $existingCustomer->id !== $customer->id) {
            $this->sendMessage($chatId, "❌ This Telegram account is already linked to another user. Please unlink it first.");
            return;
        }

        // Link the accounts
        $telegramUser = $message->getFrom();
        $customer->linkTelegram(
            $chatId,
            $telegramUser->getUsername(),
            $telegramUser->getFirstName(),
            $telegramUser->getLastName()
        );

        // Remove the linking code from cache
        cache()->forget($cacheKey);

        // Send success message
        $this->sendMessage($chatId, "✅ Account linked successfully!\n\nWelcome, {$customer->name}! 🎉\n\nYour Telegram account is now connected to your 1000proxy account. Type /help to see available commands.");

        Log::info('Telegram account linked', [
            'customer_id' => $customer->id,
            'telegram_chat_id' => $chatId,
            'telegram_username' => $telegramUser->getUsername()
        ]);
    }

    /**
     * Send message to Telegram chat
     */
    protected function sendMessage(int $chatId, string $text): void
    {
        try {
            // Outgoing rate limit per chat: 20 msgs/min
            if (class_exists(\Illuminate\Support\Facades\RateLimiter::class)) {
                $key = 'tg_out:' . $chatId;
                if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 20)) {
                    // Skip sending to avoid hitting Telegram limits
                    Log::warning('Telegram outgoing rate limit hit', ['chat_id' => $chatId]);
                    return;
                }
                \Illuminate\Support\Facades\RateLimiter::hit($key, 60);
            }
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Public method to send direct message (for controller access)
     */
    public function sendDirectMessage(int $chatId, string $text): void
    {
        $this->sendMessage($chatId, $text);
    }

    /**
     * Send message with inline keyboard
     */
    protected function sendMessageWithKeyboard(int $chatId, string $text, array $keyboard): void
    {
        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($keyboard)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message with keyboard', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format traffic in human readable format
     */
    protected function formatTraffic(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = 1024;
        $exponent = floor(log($bytes, $base));
        $value = round($bytes / pow($base, $exponent), 2);

        return $value . ' ' . $units[$exponent];
    }

    /**
     * Get order status icon
     */
    protected function getOrderStatusIcon(string $status): string
    {
        return match($status) {
            'pending' => '⏳',
            'processing' => '🔄',
            'completed' => '✅',
            'failed' => '❌',
            'cancelled' => '⏹️',
            default => '📋'
        };
    }

    /**
     * Send order completion notification
     */
    public function sendOrderNotification(Order $order): void
    {
        $user = $order->user;

        if (!$user->telegram_id) {
            return;
        }

        $message = "🎉 Your Proxy is Ready!\n\n";
        $message .= "📋 Order ID: #{$order->id}\n";
        $message .= "🌐 Server: {$order->server->location}\n";
        $message .= "📊 Status: {$order->status}\n\n";

        if ($order->status === 'completed') {
            $message .= "🔗 Configuration:\n";
            $message .= "Visit " . config('app.url') . "/orders/{$order->id} to get your proxy configuration.\n\n";
            $message .= "📱 QR Code and setup instructions are available on your dashboard.";
        } else {
            $message .= "❌ There was an issue with your order. Please contact support or visit your dashboard for more details.";
        }

        $this->sendMessage($user->telegram_id, $message);
    }

    /**
     * Handle callback queries from inline keyboards
     */
    protected function handleCallbackQuery($callbackQuery): void
    {
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $data = $callbackQuery->getData();
        $userId = $callbackQuery->getFrom()->getId();

        // Handle different callback actions
        if (str_starts_with($data, 'reset_confirm_')) {
            $clientId = substr($data, 14);
            $this->handleResetConfirm($chatId, $userId, $clientId);
        } elseif (str_starts_with($data, 'reset_cancel_')) {
            $cid = substr($data, 13);
            $this->sendMessage($chatId, "❌ Reset cancelled for client {$cid}");
        } elseif (str_starts_with($data, 'server_page_')) {
            $page = (int) substr($data, 12);
            $this->handleServersPage($chatId, $userId, $page);
        } elseif (str_starts_with($data, 'buy_plan_')) {
            $planId = (int) substr($data, 9);
            // If not linked yet, store pending buy and start signup
            if (!Customer::where('telegram_chat_id', $chatId)->exists()) {
                cache()->put("tg_pending_buy_{$chatId}", $planId, now()->addMinutes(10));
                $this->sendMessage($chatId, "🛒 You'll need an account to purchase. Let's create one first.");
                $this->handleSignup($chatId, $userId);
            } else {
                $this->handleBuyConfirm($chatId, $userId, $planId);
            }
        } elseif (str_starts_with($data, 'confirm_buy_')) {
            $planId = (int) substr($data, 12);
            // Proceed to create order immediately
            $this->handleBuy($chatId, $userId, (string) $planId);
        } elseif ($data === 'cancel_buy') {
            $this->sendMessage($chatId, '🛑 Purchase cancelled.');
    } else {
            switch ($data) {
                case 'refresh_balance':
                    $this->handleBalance($chatId, $userId);
                    break;

                case 'view_servers':
                    $this->handleServersPage($chatId, $userId, 1);
                    break;

                case 'view_myproxies':
                    $this->handleMyProxies($chatId, $userId);
                    break;

                case 'view_orders':
                    $this->handleOrders($chatId, $userId);
                    break;

                case 'admin_panel':
                    $this->handleAdminPanel($chatId, $userId);
                    break;

                case 'server_health':
                    $this->handleServerHealth($chatId, $userId);
                    break;

                case 'user_stats':
                    $this->handleUserStats($chatId, $userId);
                    break;

                case 'system_stats':
                    $this->handleSystemStats($chatId, $userId);
                    break;

                case 'open_support':
                    $this->handleSupport($chatId, $userId, '');
                    break;

                case 'signup_start':
                    $this->handleSignup($chatId, $userId);
                    break;

                case 'profile_update_name':
                    $this->handleProfileUpdateCallback($chatId, 'name');
                    break;

                case 'profile_update_email':
                    $this->handleProfileUpdateCallback($chatId, 'email');
                    break;
            }
        }

        // Answer callback query to remove loading state
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId()
        ]);
    }

    /**
     * Begin signup flow: ask for email, then name; create Customer and link to chat.
     */
    protected function handleSignup(int $chatId, int $userId): void
    {
        if (Customer::where('telegram_chat_id', $chatId)->exists()) {
            $this->sendMessage($chatId, '✅ Your Telegram is already linked. Use /menu.');
            return;
        }
        cache()->put("tg_flow_{$chatId}", [
            'name' => 'signup',
            'step' => 'email'
        ], now()->addMinutes(10));
        $this->sendMessage($chatId, "🆕 Let's create your account.\nPlease enter your email address:");
    }

    /**
     * View or update profile (name/email) for linked customers
     */
    protected function handleProfile(int $chatId, int $userId): void
    {
        $customer = Customer::where('telegram_chat_id', $chatId)->first();
        if (!$customer) {
            $this->sendMessage($chatId, 'You need an account to manage your profile. Tap "🆕 Create Account".');
            return;
        }

        $text = "👤 Your Profile\n\n";
        $text .= "Name: " . ($customer->name ?: '—') . "\n";
        $text .= "Email: " . ($customer->email ?: '—') . "\n\n";
        $kb = [ 'inline_keyboard' => [
            [ ['text' => '✏️ Update Name', 'callback_data' => 'profile_update_name'] ],
            [ ['text' => '✉️ Update Email', 'callback_data' => 'profile_update_email'] ],
        ]];
        $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }

    /**
     * Handle ongoing conversational flows based on cached state
     */
    protected function handleOngoingFlow(int $chatId, int $userId, string $text): bool
    {
        $state = cache()->get("tg_flow_{$chatId}");
        if (!$state || !is_array($state)) return false;

        // Signup flow
        if (($state['name'] ?? null) === 'signup') {
            if (($state['step'] ?? null) === 'email') {
                $email = trim($text);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->sendMessage($chatId, '❌ Invalid email. Please enter a valid email address.');
                    return true;
                }
                $state['email'] = $email;
                $state['step'] = 'name';
                cache()->put("tg_flow_{$chatId}", $state, now()->addMinutes(10));
                $this->sendMessage($chatId, 'Great! Now enter your name:');
                return true;
            }
            if (($state['step'] ?? null) === 'name') {
                $name = trim($text);
                if ($name === '' || mb_strlen($name) < 2) {
                    $this->sendMessage($chatId, 'Please enter a valid name (at least 2 characters).');
                    return true;
                }
                // Create customer if email free; otherwise attach if unlinked
                $email = $state['email'];
                $existing = Customer::where('email', $email)->first();
                if ($existing) {
                    if ($existing->telegram_chat_id && $existing->telegram_chat_id != $chatId) {
                        $this->sendMessage($chatId, '❌ This email is already linked to another Telegram.');
                        cache()->forget("tg_flow_{$chatId}");
                        return true;
                    }
                    $customer = $existing;
                    $customer->name = $name;
                } else {
                    $customer = new Customer();
                    $customer->email = $email;
                    $customer->name = $name;
                    $customer->is_active = true;
                    // Generate a random password the user can reset on web later
                    $customer->password = bcrypt(Str::random(16));
                }
                $customer->telegram_chat_id = $chatId;
                $customer->telegram_username = null;
                $customer->save();

                cache()->forget("tg_flow_{$chatId}");
                $this->sendMessage($chatId, "✅ Account ready, {$customer->name}!\nYou're linked to this Telegram chat.");

                // If there is a pending buy, resume it
                $pendingPlanId = cache()->pull("tg_pending_buy_{$chatId}");
                if ($pendingPlanId) {
                    $this->sendMessage($chatId, '🔁 Resuming your pending purchase…');
                    $this->handleBuyConfirm($chatId, $userId, (int) $pendingPlanId);
                } else {
                    // Otherwise open menu
                    $this->handleMenu($chatId, $userId);
                }
                return true;
            }
        }

        // Profile updates
        if (($state['name'] ?? null) === 'profile_update_name') {
            $name = trim($text);
            if ($name === '' || mb_strlen($name) < 2) {
                $this->sendMessage($chatId, 'Please enter a valid name (at least 2 characters).');
                return true;
            }
            $customer = Customer::where('telegram_chat_id', $chatId)->first();
            if ($customer) {
                $customer->name = $name;
                $customer->save();
                $this->sendMessage($chatId, '✅ Name updated.');
            }
            cache()->forget("tg_flow_{$chatId}");
            return true;
        }

        if (($state['name'] ?? null) === 'profile_update_email') {
            $email = trim($text);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendMessage($chatId, '❌ Invalid email. Please enter a valid email address.');
                return true;
            }
            if (Customer::where('email', $email)->where('telegram_chat_id', '!=', $chatId)->exists()) {
                $this->sendMessage($chatId, '❌ That email is already used by another account.');
                return true;
            }
            $customer = Customer::where('telegram_chat_id', $chatId)->first();
            if ($customer) {
                $customer->email = $email;
                $customer->save();
                $this->sendMessage($chatId, '✅ Email updated.');
            }
            cache()->forget("tg_flow_{$chatId}");
            return true;
        }

        return false;
    }

    /**
     * Handle profile update callbacks to prompt for new values
     */
    protected function handleProfileUpdateCallback(int $chatId, string $type): void
    {
        if (!Customer::where('telegram_chat_id', $chatId)->exists()) {
            $this->sendMessage($chatId, 'You need an account to manage your profile. Tap "🆕 Create Account".');
            return;
        }
        $stateName = $type === 'name' ? 'profile_update_name' : 'profile_update_email';
        cache()->put("tg_flow_{$chatId}", [ 'name' => $stateName ], now()->addMinutes(10));
        $prompt = $type === 'name' ? 'Please enter your new name:' : 'Please enter your new email:';
        $this->sendMessage($chatId, $prompt);
    }
}
