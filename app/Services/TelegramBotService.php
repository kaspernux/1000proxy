<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\ServerPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\ServerClient;
use App\Models\Server;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Telegram\Bot\Keyboard\Keyboard;

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

            // Locale detection: prefer Telegram user's language_code, map to supported locales
            try {
                $langCode = null;
                if ($update->getMessage()) {
                    $langCode = $update->getMessage()->getFrom()->getLanguageCode();
                } elseif ($update->getCallbackQuery()) {
                    $langCode = $update->getCallbackQuery()->getFrom()->getLanguageCode();
                }
                $supported = ['en','ru','fr','zh','ar','hi','es','pt'];
                $map = [
                    'en' => 'en', 'en-us' => 'en', 'en-gb' => 'en',
                    'ru' => 'ru', 'fr' => 'fr', 'zh' => 'zh', 'zh-hans' => 'zh', 'zh-cn' => 'zh', 'zh-hant' => 'zh',
                    'ar' => 'ar', 'hi' => 'hi', 'es' => 'es', 'pt' => 'pt', 'pt-br' => 'pt',
                ];
                $code = strtolower((string) $langCode);
                $locale = $map[$code] ?? ($map[substr($code, 0, 2)] ?? 'en');
                if (!in_array($locale, $supported, true)) {
                    $locale = 'en';
                }
                app()->setLocale($locale);
            } catch (\Throwable $e) {
                // Default to English on any error
                app()->setLocale('en');
            }

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

        // Map pretty reply-keyboard labels to commands (no leading '/')
        if ($text && !str_starts_with($text, '/')) {
            // Map localized reply-keyboard labels to commands
            $labelMap = [
                '✨ ' . __('telegram.buttons.menu') => '/menu',
                '🛒 ' . __('telegram.buttons.plans') => '/plans',
                '📦 ' . __('telegram.buttons.orders') => '/orders',
                '🧰 ' . __('telegram.buttons.my_services') => '/myproxies',
                '💳 ' . __('telegram.buttons.wallet') => '/balance',
                '🆘 ' . __('telegram.buttons.support') => '/support',
                '👤 ' . __('telegram.buttons.profile') => '/profile',
                '🆕 ' . __('telegram.buttons.sign_up') => '/signup',
            ];
            if (isset($labelMap[$text])) {
                $text = $labelMap[$text];
            }
        }

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
                        $this->sendMessage($chatId, __('telegram.messages.unknown'));
                    }
                }
        }
    }

    /**
     * Handle /start command
     */
    protected function handleStart(int $chatId, int $userId, string $params): void
    {
        // Always show a persistent command keyboard first
        $this->sendPersistentCommandMenu($chatId);

        // If a customer is already linked, jump to main menu
        $customer = Customer::where('telegram_chat_id', $chatId)->first();
        if ($customer) {
            $this->handleMenu($chatId, $userId);
            return;
        }

    // Not linked: friendly onboarding with CTAs (localized)
    $message = __('telegram.messages.start_welcome', ['url' => config('app.url')]);

        $keyboard = [
            'inline_keyboard' => [
                [ ['text' => '🌐 ' . __('telegram.common.browse_plans'), 'callback_data' => 'view_servers'] ],
                [ ['text' => '🆕 ' . __('telegram.common.create_account'), 'callback_data' => 'signup_start'] ],
                [ ['text' => '❓ ' . __('telegram.common.help'), 'callback_data' => 'open_support'] ],
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
            $this->sendMessage($chatId, __('telegram.messages.no_services'));
            return;
        }
        // Precompute used
        $usedMap = [];
        foreach ($clients as $client) {
            $usedMap[$client->id] = $this->formatTraffic(($client->remote_up ?? 0) + ($client->remote_down ?? 0));
        }
        $text = $this->renderView('telegram.services', [
            'clients' => $clients,
            'usedMap' => $usedMap,
        ]);
    $this->sendMessage($chatId, $text . "\n" . __('telegram.messages.use_config_hint') . "\n" . __('telegram.messages.open_dashboard', ['url' => config('app.url') . '/dashboard']));
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
        $message = $this->renderView('telegram.topup', [
            'currentBalance' => $currentBalance,
        ]);

        // Create inline keyboard for quick top-up amounts via builder
    $kb = Keyboard::make()->inline()
            ->row(
                Keyboard::inlineButton(['text' => '$10', 'url' => config('app.url') . '/wallet?amount=10']),
                Keyboard::inlineButton(['text' => '$25', 'url' => config('app.url') . '/wallet?amount=25']),
                Keyboard::inlineButton(['text' => '$50', 'url' => config('app.url') . '/wallet?amount=50'])
            )
            ->row(
                Keyboard::inlineButton(['text' => '$100', 'url' => config('app.url') . '/wallet?amount=100']),
        Keyboard::inlineButton(['text' => __('telegram.buttons.custom_amount'), 'url' => config('app.url') . '/wallet'])
            );

    $this->sendMessageWithKeyboard($chatId, $message, $kb);
    }

    /**
     * Handle /config command
     */
    protected function handleConfig(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $this->sendMessage($chatId, __('telegram.messages.config_need_id'));
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
            $this->sendMessage($chatId, __('telegram.messages.config_not_found'));
            return;
        }

        $config = $client->getDownloadableConfig();
        $text = $this->renderView('telegram.config', [
            'planName' => $client->plan->name ?? '—',
            'server' => $client->inbound->server->ip ?? '—',
            'clientLink' => $config['client_link'] ?? null,
            'subscriptionLink' => $config['subscription_link'] ?? null,
            'jsonLink' => $config['json_link'] ?? null,
        ]);

        $this->sendMessage($chatId, $text);
    }

    /**
     * Handle /reset command
     */
    protected function handleReset(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $this->sendMessage($chatId, __('telegram.messages.reset_need_id'));
            return;
        }

        $client = ServerClient::where('customer_id', $customer->id)->find(trim($params));
        if (!$client) {
            $this->sendMessage($chatId, __('telegram.messages.client_not_found'));
            return;
        }

        // Send confirmation keyboard (builder)
        $keyboard = Keyboard::make()->inline()->row(
            Keyboard::inlineButton(['text' => '✅ ' . __('telegram.buttons.yes_reset'), 'callback_data' => "reset_confirm_{$client->id}"]),
            Keyboard::inlineButton(['text' => '❌ ' . __('telegram.buttons.cancel'), 'callback_data' => "reset_cancel_{$client->id}"])
        );

        $text = $this->renderView('telegram.reset_confirm', [
            'planName' => $client->plan->name ?? '—',
            'server' => $client->inbound->server->ip ?? '—',
        ]);

        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    /**
     * Handle /status command
     */
    protected function handleStatus(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $activeServices = $customer->clients()->count();
            $wallet = $customer->wallet;
            $balance = $wallet ? $wallet->balance : 0;
            $text = $this->renderView('telegram.status_account', [
                'name' => $customer->name,
                'balance' => $balance,
                'activeServices' => $activeServices,
                'memberSince' => $customer->created_at->format('M j, Y'),
            ]);

            $this->sendMessage($chatId, $text);
            return;
        }

        // Show specific client status
        $client = ServerClient::where('customer_id', $customer->id)->find(trim($params));
        if (!$client) {
            $this->sendMessage($chatId, __('telegram.messages.service_not_found'));
            return;
        }

        $text = $this->renderView('telegram.status_client', [
            'planName' => $client->plan->name ?? '—',
            'server' => $client->inbound->server->ip ?? '—',
            'connection' => (bool)($client->enable ?? false),
            'upload' => $this->formatTraffic((int)($client->remote_up ?? 0)),
            'download' => $this->formatTraffic((int)($client->remote_down ?? 0)),
            'total' => $this->formatTraffic((int)(($client->remote_up ?? 0) + ($client->remote_down ?? 0))),
            'resets' => (int)($client->reset ?? 0),
            'created' => ($client->created_at?->format('M j, Y H:i') ?? '—'),
            'clientId' => $client->id,
        ]);

        $this->sendMessage($chatId, $text);
    }

    /**
     * Show the main inline menu with quick actions
     */
    protected function handleMenu(int $chatId, int $userId): void
    {
        $keyboard = Keyboard::make()->inline();
        $keyboard->row(
            Keyboard::inlineButton(['text' => '🧰 ' . __('telegram.buttons.my_services'), 'callback_data' => 'view_myproxies']),
            Keyboard::inlineButton(['text' => '🛒 ' . __('telegram.buttons.plans'), 'callback_data' => 'view_servers'])
        );
        $keyboard->row(
            Keyboard::inlineButton(['text' => '💳 ' . __('telegram.buttons.wallet'), 'callback_data' => 'refresh_balance']),
            Keyboard::inlineButton(['text' => '📦 ' . __('telegram.buttons.orders'), 'callback_data' => 'view_orders'])
        );
        $keyboard->row(
            Keyboard::inlineButton(['text' => '🆘 ' . __('telegram.buttons.support'), 'callback_data' => 'open_support'])
        );

        // If linked staff, add admin row
        $staff = $this->getStaffUser($chatId, $userId);
        if ($staff && in_array($staff->role, ['admin', 'support_manager', 'sales_support'])) {
            $keyboard->row(Keyboard::inlineButton(['text' => '🛠 ' . __('telegram.buttons.admin'), 'callback_data' => 'admin_panel']));
        }

        $text = $this->renderView('telegram.menu');
        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
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

        $text = $this->renderView('telegram.balance', [
            'balance' => $balance,
        ]);

        $this->sendMessage($chatId, $text);
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
            $this->sendMessage($chatId, __('telegram.messages.no_plans'));
            return;
        }

        $text = $this->renderView('telegram.plans', [
            'plans' => $plans,
            'page' => 1,
            'totalPages' => 1,
        ]);

        if ($customer) {
            $this->sendMessage($chatId, $text . "\n" . __('telegram.messages.use_buy_compact'));
        } else {
            $keyboard = Keyboard::make()->inline()->row(
                Keyboard::inlineButton(['text' => '🆕 ' . __('telegram.common.create_account'), 'callback_data' => 'signup_start'])
            )->row(
                Keyboard::inlineButton(['text' => '📋 ' . __('telegram.buttons.how_to_link_later'), 'callback_data' => 'open_support'])
            );
            $this->sendMessageWithKeyboard($chatId, $text . "\n" . __('telegram.messages.purchase_requires_account'), $keyboard);
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
            $this->sendMessage($chatId, __('telegram.messages.no_orders'));
            return;
        }
        $text = $this->renderView('telegram.orders', [
            'orders' => $orders,
        ]);
        $kb = Keyboard::make()->inline();
        foreach ($orders as $order) {
            $icon = $this->getOrderStatusIcon($order->order_status ?? '');
            $amount = (float) ($order->grand_amount ?? $order->total_amount ?? 0);
            $kb->row(Keyboard::inlineButton([
                'text' => $icon . ' #' . $order->id . ' • $' . number_format($amount, 2),
                'url' => config('app.url') . '/orders/' . $order->id,
            ]));
        }
        $kb->row(Keyboard::inlineButton([
            'text' => '🧾 ' . __('telegram.buttons.open_orders'),
            'url' => config('app.url') . '/orders'
        ]));
        $this->sendMessageWithKeyboard($chatId, $text, $kb);
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
            $this->sendMessage($chatId, __('telegram.messages.buy_need_plan'));
            return;
        }

        $plan = ServerPlan::with('server')->find($planId);
        if (!$plan || !$plan->isAvailable()) {
            $this->sendMessage($chatId, __('telegram.messages.plan_unavailable'));
            return;
        }

        // Check wallet balance
        $wallet = $customer->wallet;
        $price = (float) $plan->getTotalPrice();
        if (!$wallet || $wallet->balance < $price) {
            $this->sendMessage($chatId, __('telegram.messages.insufficient_balance', ['url' => config('app.url') . '/wallet']));
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

            $this->sendMessage($chatId, __('telegram.messages.order_failed'));
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
            $text = $this->renderView('telegram.support_options');
            $this->sendMessage($chatId, $text);
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

        $text = $this->renderView('telegram.support_sent', [
            'customerName' => $customer->name,
            'messageText' => $params,
        ]);

        $this->sendMessage($chatId, $text);
    }

    /**
     * Handle /help command
     */
    protected function handleHelp(int $chatId): void
    {
    $text = $this->renderView('telegram.help');
    $this->sendMessage($chatId, $text);
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
            $this->sendMessage($chatId, __('telegram.messages.client_not_found_or_reset'));
            return;
        }

        try {
            // Prefer local reset with sync; fallback to service
            $client->resetTraffic();
            $text = $this->renderView('telegram.reset_success', [
                'planName' => $client->plan->name ?? '—',
                'server' => $client->inbound->server->ip ?? '—',
                'clientId' => $client->id,
            ]);

            $this->sendMessage($chatId, $text);

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
            $this->sendMessage($chatId, __('telegram.messages.no_plans_page', ['page' => $page]));
            return;
        }

        // Render plans page
        $text = $this->renderView('telegram.plans', [
            'plans' => $plans,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);

        // Build keyboard (pagination + buy buttons)
        $kb = Keyboard::make()->inline();
        $nav = [];
        if ($page > 1) {
            $nav[] = Keyboard::inlineButton(['text' => '◀️ ' . __('telegram.common.prev'), 'callback_data' => 'server_page_' . ($page - 1)]);
        }
        $nav[] = Keyboard::inlineButton(['text' => '📄 ' . $page . '/' . $totalPages, 'callback_data' => 'noop']);
        if ($page < $totalPages) {
            $nav[] = Keyboard::inlineButton(['text' => __('telegram.common.next') . ' ▶️', 'callback_data' => 'server_page_' . ($page + 1)]);
        }
        if (!empty($nav)) {
            $kb->row(...$nav);
        }
        foreach ($plans as $plan) {
            $kb->row(Keyboard::inlineButton([
                'text' => '🛒 ' . __('telegram.buttons.buy_plan', ['name' => $plan->name, 'price' => '$' . number_format((float)$plan->price, 2)]),
                'callback_data' => 'buy_plan_' . $plan->id,
            ]));
        }
        if (!$isLinked) {
            $kb->row(Keyboard::inlineButton(['text' => '🆕 ' . __('telegram.common.create_account'), 'callback_data' => 'signup_start']));
        }

        $this->sendMessageWithKeyboard($chatId, $text, $kb);
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
            $this->sendMessage($chatId, __('telegram.messages.plan_not_available'));
            return;
        }

        $wallet = $customer->wallet;
        if (!$wallet || $wallet->balance < $plan->price) {
            $message = __('telegram.messages.buy_insufficient') . "\n\n";
            $message .= "💰 " . __('telegram.messages.required_amount', ['amount' => '$' . number_format((float)$plan->price, 2)]) . "\n";
            $message .= "💳 " . __('telegram.messages.current_balance', ['amount' => '$' . number_format($wallet ? $wallet->balance : 0, 2)]) . "\n\n";
            $message .= __('telegram.messages.buy_topup_hint');

            $keyboard = [
                'inline_keyboard' => [
                    [ ['text' => '💳 ' . __('telegram.buttons.topup_wallet'), 'url' => config('app.url') . '/wallet'] ],
                    [ ['text' => '🔄 ' . __('telegram.buttons.topped_up_refresh'), 'callback_data' => 'refresh_balance'] ],
                ]
            ];

            $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
            return;
        }

        // Create confirmation keyboard
        $keyboard = Keyboard::make()->inline()->row(
            Keyboard::inlineButton(['text' => '✅ ' . __('telegram.buttons.confirm_purchase'), 'callback_data' => "confirm_buy_{$planId}"]),
            Keyboard::inlineButton(['text' => '❌ ' . __('telegram.buttons.cancel'), 'callback_data' => 'cancel_buy'])
        );

        $message = '🛒 ' . __('telegram.messages.buy_confirm_title') . "\n\n";
        $message .= '📦 ' . __('telegram.messages.buy_confirm_plan', ['plan' => $plan->name]) . "\n";
        $message .= '🌐 ' . __('telegram.messages.buy_confirm_server', ['server' => ($plan->server->country ?? $plan->server->ip ?? '—')]) . "\n";
        $message .= '💵 ' . __('telegram.messages.buy_confirm_price', ['price' => '$' . number_format((float)$plan->price, 2)]) . "\n";
        $message .= '💰 ' . __('telegram.messages.buy_confirm_balance', ['balance' => '$' . number_format($wallet->balance, 2)]) . "\n";
        $message .= '💳 ' . __('telegram.messages.buy_confirm_after', ['after' => '$' . number_format($wallet->balance - (float)$plan->price, 2)]) . "\n\n";
        $message .= __('telegram.messages.buy_confirm_proceed');

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle admin panel
     */
    protected function handleAdminPanel(int $chatId, int $userId): void
    {
    $user = $this->getAuthenticatedUser($chatId, $userId);
    if (!$user || !$this->isAdmin($user)) {
        $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

    $keyboard = Keyboard::make()->inline()
            ->row(
                Keyboard::inlineButton(['text' => '👥 ' . __('telegram.buttons.users'), 'callback_data' => 'user_stats']),
                Keyboard::inlineButton(['text' => '🌐 ' . __('telegram.buttons.servers'), 'callback_data' => 'server_health'])
            )
            ->row(
                Keyboard::inlineButton(['text' => '📊 ' . __('telegram.buttons.statistics'), 'callback_data' => 'system_stats']),
                Keyboard::inlineButton(['text' => '📢 ' . __('telegram.buttons.broadcast'), 'callback_data' => 'admin_broadcast'])
            )
            ->row(
                Keyboard::inlineButton(['text' => '🔄 ' . __('telegram.buttons.refresh'), 'callback_data' => 'admin_panel'])
            );

    $text = $this->renderView('telegram.admin.panel');

    $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    /**
     * Handle admin users command
     */
    protected function handleAdminUsers(int $chatId, int $userId, string $params): void
    {
        $staff = $this->getStaffUser($chatId, $userId);
        if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

        if (empty($params)) {
            // Show user statistics
            $totalUsers = Customer::count();
            $activeUsers = Customer::whereHas('clients')->count();
            $telegramUsers = Customer::whereNotNull('telegram_chat_id')->count();
            $recentUsers = Customer::where('created_at', '>=', now()->subDays(7))->count();

            $text = $this->renderView('telegram.admin.user_stats', [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'telegramUsers' => $telegramUsers,
                'recentUsers' => $recentUsers,
            ]);

            $this->sendMessage($chatId, $text);
            return;
        }

        // Search for specific customer
        $searchUser = Customer::where('email', 'like', "%{$params}%")
            ->orWhere('name', 'like', "%{$params}%")
            ->first();

        if (!$searchUser) {
            $this->sendMessage($chatId, __('telegram.messages.user_not_found', ['q' => $params]));
            return;
        }
        $orders = $searchUser->orders()->count();
        $activeOrders = $searchUser->clients()->count();
        $wallet = $searchUser->wallet;
        $balance = $wallet ? $wallet->balance : 0;

        $text = $this->renderView('telegram.admin.user_details', [
            'email' => $searchUser->email,
            'name' => $searchUser->name,
            'balance' => $balance,
            'orders' => $orders,
            'activeOrders' => $activeOrders,
            'telegramLinked' => (bool)$searchUser->telegram_chat_id,
            'joined' => $searchUser->created_at->format('M j, Y'),
            'lastLogin' => ($searchUser->last_login_at ? $searchUser->last_login_at->format('M j, Y H:i') : 'Never'),
        ]);

        $this->sendMessage($chatId, $text);
    }

    /**
     * Handle server health
     */
    protected function handleServerHealth(int $chatId, int $userId, int $page = 1): void
    {
    $staff = $this->getStaffUser($chatId, $userId);
    if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $query = Server::with(['brand', 'category']);
        $totalServers = (clone $query)->count();
        $activeServers = (clone $query)->where('status', 'active')->count();
        $inactiveServers = (clone $query)->where('status', 'inactive')->count();
        $totalPages = max(1, (int) ceil($totalServers / $perPage));

        $servers = Server::with(['brand', 'category'])
            ->orderBy('location')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $serversList = [];
        foreach ($servers as $server) {
            $serversList[] = [
                'statusIcon' => $server->status === 'active' ? '✅' : '❌',
                'location' => $server->location,
                'loadIcon' => ($server->load > 80 ? '🔴' : ($server->load > 60 ? '🟡' : '🟢')),
                'load' => (int)($server->load ?? 0),
                'price' => (float)($server->price ?? 0),
            ];
        }

        $text = $this->renderView('telegram.admin.server_health', [
            'totalServers' => $totalServers,
            'activeServers' => $activeServers,
            'inactiveServers' => $inactiveServers,
            'servers' => $serversList,
            'remaining' => max(0, $totalServers - ($page * $perPage)),
        ]);

        // Pagination keyboard
        $kb = Keyboard::make()->inline();
        $nav = [];
        if ($page > 1) {
            $nav[] = Keyboard::inlineButton(['text' => '◀️ ' . __('telegram.common.prev'), 'callback_data' => 'server_health_page_' . ($page - 1)]);
        }
        $nav[] = Keyboard::inlineButton(['text' => '📄 ' . $page . '/' . $totalPages, 'callback_data' => 'noop']);
        if ($page < $totalPages) {
            $nav[] = Keyboard::inlineButton(['text' => __('telegram.common.next') . ' ▶️', 'callback_data' => 'server_health_page_' . ($page + 1)]);
        }
        if (!empty($nav)) {
            $kb->row(...$nav);
        }

        $this->sendMessageWithKeyboard($chatId, $text, $kb);
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
            $this->sendMessage($chatId, __('telegram.messages.access_denied'));
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

        // Server statistics
        $totalServers = \App\Models\Server::count();
        $activeServers = \App\Models\Server::where('status', 'up')->count();
        $avgLoad = \App\Models\Server::where('status', 'up')->avg('load') ?? 0;

        $text = $this->renderView('telegram.admin.system_stats', [
            'totalUsers' => $totalUsers,
            'totalOrders' => $totalOrders,
            'completedOrders' => $completedOrders,
            'pendingOrders' => $pendingOrders,
            'totalRevenue' => $totalRevenue,
            'todayRevenue' => $todayRevenue,
            'todayOrders' => $todayOrders,
            'totalServers' => $totalServers,
            'activeServers' => $activeServers,
            'avgLoad' => $avgLoad,
            'updatedAt' => now()->format('H:i:s'),
        ]);

        $this->sendMessage($chatId, $text);
    }

    /**
     * Handle broadcast message
     */
    protected function handleBroadcast(int $chatId, int $userId, string $params): void
    {
        $staff = $this->getStaffUser($chatId, $userId);
        if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

        if (empty($params)) {
            $text = $this->renderView('telegram.admin.broadcast_help');
            $this->sendMessage($chatId, $text);
            return;
        }

        // Get all users with Telegram linked
    $telegramUsers = Customer::whereNotNull('telegram_chat_id')->get();
        $sentCount = 0;
        $failedCount = 0;

    $broadcastMessage = __('telegram.messages.broadcast_title') . "\n\n{$params}\n\n" . __('telegram.messages.broadcast_footer');

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

        $text = $this->renderView('telegram.admin.broadcast_result', [
            'sent' => $sentCount,
            'failed' => $failedCount,
            'total' => $telegramUsers->count(),
        ]);

        $this->sendMessage($chatId, $text);

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
            $this->sendMessage($chatId, __('telegram.messages.link_account_first', ['url' => config('app.url')]));
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
            $this->sendMessage($chatId, __('telegram.messages.code_invalid'));
            return;
        }

        // Get customer from database
        $customer = Customer::find($webUserId);
        if (!$customer) {
            $this->sendMessage($chatId, __('telegram.messages.user_not_found_simple'));
            return;
        }

        // Check if this Telegram account is already linked to another user
        $existingCustomer = Customer::where('telegram_chat_id', $chatId)->first();
        if ($existingCustomer && $existingCustomer->id !== $customer->id) {
            $this->sendMessage($chatId, __('telegram.messages.telegram_already_linked'));
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
    $this->sendMessage($chatId, __('telegram.messages.linking_success', ['name' => $customer->name]));

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
    protected function sendMessageWithKeyboard(int $chatId, string $text, $keyboard): void
    {
        try {
            // Allow Keyboard builder instance or plain array
            $replyMarkup = $keyboard;
            if (is_array($keyboard)) {
                $replyMarkup = json_encode($keyboard);
            }
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message with keyboard', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Render a Blade view into a Telegram-safe HTML message.
     */
    protected function renderView(string $view, array $data = []): string
    {
        try {
            $html = trim(view($view, $data)->render());
            // Telegram supports a subset of HTML; keep it simple
            return $html;
        } catch (\Throwable $e) {
            Log::error('Telegram view render failed', ['view' => $view, 'error' => $e->getMessage()]);
            return '⚠️ Failed to render message.';
        }
    }

    /**
     * Send a persistent reply keyboard with common commands so it's always visible.
     */
    protected function sendPersistentCommandMenu(int $chatId): void
    {
        $isLinked = Customer::where('telegram_chat_id', $chatId)->exists();
        $keyboard = $isLinked
            ? [
                'keyboard' => [
                    [ ['text' => '✨ ' . __('telegram.buttons.menu')], ['text' => '🛒 ' . __('telegram.buttons.plans')], ['text' => '📦 ' . __('telegram.buttons.orders')] ],
                    [ ['text' => '🧰 ' . __('telegram.buttons.my_services')], ['text' => '💳 ' . __('telegram.buttons.wallet')], ['text' => '🆘 ' . __('telegram.buttons.support')] ],
                    [ ['text' => '👤 ' . __('telegram.buttons.profile')] ],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
                'one_time_keyboard' => false,
                'input_field_placeholder' => __('telegram.messages.quick_actions_placeholder'),
            ]
            : [
                'keyboard' => [
                    [ ['text' => '🛒 ' . __('telegram.buttons.plans')], ['text' => '🆕 ' . __('telegram.buttons.sign_up')], ['text' => '🆘 ' . __('telegram.buttons.support')] ],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
                'one_time_keyboard' => false,
                'input_field_placeholder' => __('telegram.messages.quick_actions_guest_placeholder'),
            ];

        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => __('telegram.messages.quick_actions_below'),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($keyboard)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send persistent command menu', [
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
        $customer = $order->customer;
        if (!$customer || !$customer->telegram_chat_id) {
            return;
        }

        // Resolve a user-friendly server label from the first order item
        $firstItem = $order->items()->with('serverPlan.server')->first();
        $server = $firstItem?->serverPlan?->server;
        $serverLabel = $server->location
            ?? $server->country
            ?? $server->ip
            ?? '—';

        $status = $order->order_status ?? $order->status ?? '-';

        $message = __('telegram.messages.order_ready_title') . "\n\n";
        $message .= __('telegram.messages.order_id_line', ['id' => $order->id]) . "\n";
        $message .= __('telegram.messages.order_server_line', ['server' => $serverLabel]) . "\n";
        $message .= __('telegram.messages.order_status_line', ['status' => ucfirst((string)$status)]) . "\n\n";

        if ($status === 'completed' || $order->isFullyProvisioned()) {
            $message .= __('telegram.messages.order_completed_block', [
                'url' => config('app.url') . "/orders/{$order->id}"
            ]);
        } else {
            $message .= __('telegram.messages.order_issue');
        }

        $this->sendMessage($customer->telegram_chat_id, $message);
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
            $this->sendMessage($chatId, __('telegram.messages.reset_cancelled_client', ['cid' => $cid]));
        } elseif (str_starts_with($data, 'server_page_')) {
            $page = (int) substr($data, 12);
            $this->handleServersPage($chatId, $userId, $page);
        } elseif (str_starts_with($data, 'server_health_page_')) {
            $page = (int) substr($data, 20);
            $this->handleServerHealth($chatId, $userId, $page);
        } elseif (str_starts_with($data, 'buy_plan_')) {
            $planId = (int) substr($data, 9);
            // If not linked yet, store pending buy and start signup
            if (!Customer::where('telegram_chat_id', $chatId)->exists()) {
                cache()->put("tg_pending_buy_{$chatId}", $planId, now()->addMinutes(10));
                $this->sendMessage($chatId, __('telegram.messages.need_account_purchase'));
                $this->handleSignup($chatId, $userId);
            } else {
                $this->handleBuyConfirm($chatId, $userId, $planId);
            }
        } elseif (str_starts_with($data, 'confirm_buy_')) {
            $planId = (int) substr($data, 12);
            // Proceed to create order immediately
            $this->handleBuy($chatId, $userId, (string) $planId);
        } elseif ($data === 'cancel_buy') {
                $this->sendMessage($chatId, __('telegram.messages.purchase_cancelled'));
        } elseif ($data === 'noop') {
            // No operation: acknowledge tap without sending a new message
            // Optionally could edit message to show the same content; do nothing here
        } else {
            switch ($data) {
                case 'refresh_balance':
                    $this->handleBalance($chatId, $userId);
                    break;

                case 'view_servers':
                    $this->handleServersPage($chatId, $userId, 1);
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
            $this->sendMessage($chatId, __('telegram.messages.already_linked'));
            return;
        }
        cache()->put("tg_flow_{$chatId}", [
            'name' => 'signup',
            'step' => 'email'
        ], now()->addMinutes(10));
        $this->sendMessage($chatId, __('telegram.messages.signup_start'));
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
                    $this->sendMessage($chatId, __('telegram.messages.invalid_email'));
                    return true;
                }
                $state['email'] = $email;
                $state['step'] = 'name';
                cache()->put("tg_flow_{$chatId}", $state, now()->addMinutes(10));
                $this->sendMessage($chatId, __('telegram.messages.enter_name'));
                return true;
            }
            if (($state['step'] ?? null) === 'name') {
                $name = trim($text);
                if ($name === '' || mb_strlen($name) < 2) {
                    $this->sendMessage($chatId, __('telegram.messages.invalid_name'));
                    return true;
                }
                // Create customer if email free; otherwise attach if unlinked
                $email = $state['email'];
                $existing = Customer::where('email', $email)->first();
                if ($existing) {
                    if ($existing->telegram_chat_id && $existing->telegram_chat_id != $chatId) {
                        $this->sendMessage($chatId, __('telegram.messages.email_linked_other'));
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
                $this->sendMessage($chatId, __('telegram.messages.signup_success', ['name' => $customer->name]));

                // If there is a pending buy, resume it
                $pendingPlanId = cache()->pull("tg_pending_buy_{$chatId}");
                if ($pendingPlanId) {
                    $this->sendMessage($chatId, __('telegram.messages.resuming_purchase'));
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
                $this->sendMessage($chatId, __('telegram.messages.invalid_name'));
                return true;
            }
            $customer = Customer::where('telegram_chat_id', $chatId)->first();
            if ($customer) {
                $customer->name = $name;
                $customer->save();
                $this->sendMessage($chatId, __('telegram.messages.name_updated'));
            }
            cache()->forget("tg_flow_{$chatId}");
            return true;
        }

        if (($state['name'] ?? null) === 'profile_update_email') {
            $email = trim($text);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendMessage($chatId, __('telegram.messages.invalid_email'));
                return true;
            }
            if (Customer::where('email', $email)->where('telegram_chat_id', '!=', $chatId)->exists()) {
                $this->sendMessage($chatId, __('telegram.messages.email_in_use'));
                return true;
            }
            $customer = Customer::where('telegram_chat_id', $chatId)->first();
            if ($customer) {
                $customer->email = $email;
                $customer->save();
                $this->sendMessage($chatId, __('telegram.messages.email_updated'));
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
            $this->sendMessage($chatId, __('telegram.messages.need_account_profile'));
            return;
        }
        $stateName = $type === 'name' ? 'profile_update_name' : 'profile_update_email';
        cache()->put("tg_flow_{$chatId}", [ 'name' => $stateName ], now()->addMinutes(10));
        $prompt = $type === 'name' ? __('telegram.messages.prompt_new_name') : __('telegram.messages.prompt_new_email');
        $this->sendMessage($chatId, $prompt);
    }
}
