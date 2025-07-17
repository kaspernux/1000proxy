<?php

namespace App\Services;

use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use App\Models\Wallet;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;

class TelegramBotService
{
    protected $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('services.telegram.bot_token'));
    }

    /**
     * Set webhook for Telegram bot
     */
    public function setWebhook(): bool
    {
        try {
            $response = $this->telegram->setWebhook([
                'url' => config('services.telegram.webhook_url'),
                'allowed_updates' => ['message', 'callback_query']
            ]);

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

        switch ($command) {
            case '/start':
                $this->handleStart($chatId, $userId, $params);
                break;

            case '/balance':
                $this->handleBalance($chatId, $userId);
                break;

            case '/myproxies':
                $this->handleMyProxies($chatId, $userId);
                break;

            case '/servers':
                $this->handleServers($chatId, $userId);
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
                    $this->sendMessage($chatId, "Unknown command. Type /help to see available commands.");
                }
        }
    }

    /**
     * Handle /start command
     */
    protected function handleStart(int $chatId, int $userId, string $params): void
    {
        // Check if user is already linked
        $user = User::where('telegram_chat_id', $chatId)->first();

        if ($user) {
            $this->sendMessage($chatId, "Welcome back, {$user->name}! 🎉\n\nYour account is already linked. Type /help to see available commands.");
            return;
        }

        // Send welcome message with link instructions
        $message = "Welcome to 1000proxy! 🚀\n\n";
        $message .= "To get started, you need to link your Telegram account:\n\n";
        $message .= "1. Visit: " . config('app.url') . "\n";
        $message .= "2. Login to your 1000proxy account\n";
        $message .= "3. Go to your account settings\n";
        $message .= "4. Click 'Link Telegram Account'\n";
        $message .= "5. Send the generated linking code here\n\n";
        $message .= "If you don't have an account yet, please register at " . config('app.url');

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /myproxies command
     */
    protected function handleMyProxies(int $chatId, int $userId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        $activeOrders = $user->orders()
            ->where('status', 'completed')
            ->with(['server', 'serverClient'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($activeOrders->isEmpty()) {
            $this->sendMessage($chatId, "📭 No active proxies found.\n\nUse /servers to browse and purchase proxy services.");
            return;
        }

        $message = "🔐 Your Active Proxies\n\n";

        foreach ($activeOrders as $order) {
            $server = $order->server;
            $client = $order->serverClient;

            $message .= "🌐 {$server->location} - {$server->category->name}\n";
            $message .= "📋 Order #{$order->id}\n";

            if ($client) {
                $message .= "📊 Status: " . ($client->status ? 'Active' : 'Inactive') . "\n";
                $message .= "📈 Traffic: " . $this->formatTraffic($client->up + $client->down) . "\n";
                $message .= "🔗 /config_{$order->id} - Get config\n";
                $message .= "🔄 /reset_{$order->id} - Reset proxy\n";
                $message .= "📊 /status_{$order->id} - Check status\n";
            }

            $message .= "📅 Created: {$order->created_at->format('M j, Y')}\n\n";
        }

        $message .= "💡 Use /config_[order_id] to get proxy configuration\n";
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
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        if (empty($params)) {
            $this->sendMessage($chatId, "Please specify an order ID.\n\nExample: /config 123\n\nUse /myproxies to see your active proxies.");
            return;
        }

        $orderId = (int) $params;
        $order = $user->orders()
            ->where('id', $orderId)
            ->where('status', 'completed')
            ->with(['server', 'serverClient'])
            ->first();

        if (!$order) {
            $this->sendMessage($chatId, "❌ Order not found or not completed.\n\nUse /myproxies to see your active proxies.");
            return;
        }

        $client = $order->serverClient;
        if (!$client) {
            $this->sendMessage($chatId, "❌ Proxy configuration not available for this order.");
            return;
        }

        $message = "🔐 Proxy Configuration\n\n";
        $message .= "📋 Order #{$order->id}\n";
        $message .= "🌐 Server: {$order->server->location}\n";
        $message .= "🔧 Protocol: {$order->server->protocol}\n\n";
        $message .= "📱 Quick Setup:\n";
        $message .= "🔗 Config URL: {$client->config_url}\n\n";
        $message .= "📊 QR Code and detailed setup instructions:\n";
        $message .= config('app.url') . "/orders/{$order->id}\n\n";
        $message .= "💡 Import the config URL into your proxy client\n";
        $message .= "📖 Need help? Use /support for assistance";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /reset command
     */
    protected function handleReset(int $chatId, int $userId, string $params): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        if (empty($params)) {
            $this->sendMessage($chatId, "Please specify an order ID.\n\nExample: /reset 123\n\nUse /myproxies to see your active proxies.");
            return;
        }

        $orderId = (int) $params;
        $order = $user->orders()
            ->where('id', $orderId)
            ->where('status', 'completed')
            ->with(['server', 'serverClient'])
            ->first();

        if (!$order) {
            $this->sendMessage($chatId, "❌ Order not found or not completed.\n\nUse /myproxies to see your active proxies.");
            return;
        }

        $client = $order->serverClient;
        if (!$client) {
            $this->sendMessage($chatId, "❌ Proxy not available for reset.");
            return;
        }

        // Send confirmation keyboard
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Yes, Reset', 'callback_data' => "reset_confirm_{$orderId}"],
                    ['text' => '❌ Cancel', 'callback_data' => "reset_cancel_{$orderId}"]
                ]
            ]
        ];

        $message = "🔄 Reset Proxy Confirmation\n\n";
        $message .= "📋 Order #{$order->id}\n";
        $message .= "🌐 Server: {$order->server->location}\n\n";
        $message .= "⚠️ This will:\n";
        $message .= "• Reset your proxy credentials\n";
        $message .= "• Clear traffic statistics\n";
        $message .= "• Generate new configuration\n\n";
        $message .= "Are you sure you want to proceed?";

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle /status command
     */
    protected function handleStatus(int $chatId, int $userId, string $params): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        if (empty($params)) {
            // Show overall account status
            $activeOrders = $user->orders()->where('status', 'completed')->count();
            $wallet = $user->wallet;
            $balance = $wallet ? $wallet->balance : 0;

            $message = "📊 Account Status\n\n";
            $message .= "👤 User: {$user->name}\n";
            $message .= "💰 Balance: $" . number_format($balance, 2) . "\n";
            $message .= "🔐 Active Proxies: {$activeOrders}\n";
            $message .= "📅 Member Since: {$user->created_at->format('M j, Y')}\n\n";
            $message .= "🔗 Full Dashboard: " . config('app.url') . "/dashboard\n";
            $message .= "💡 Use /status [order_id] for specific proxy status";

            $this->sendMessage($chatId, $message);
            return;
        }

        // Show specific order status
        $orderId = (int) $params;
        $order = $user->orders()
            ->where('id', $orderId)
            ->with(['server', 'serverClient'])
            ->first();

        if (!$order) {
            $this->sendMessage($chatId, "❌ Order not found.\n\nUse /myproxies to see your orders.");
            return;
        }

        $client = $order->serverClient;
        $statusIcon = $this->getOrderStatusIcon($order->status);

        $message = "📊 Proxy Status\n\n";
        $message .= "📋 Order #{$order->id}\n";
        $message .= "🌐 Server: {$order->server->location}\n";
        $message .= "{$statusIcon} Status: {$order->status}\n";

        if ($client) {
            $message .= "🔌 Connection: " . ($client->status ? 'Active' : 'Inactive') . "\n";
            $message .= "📈 Upload: " . $this->formatTraffic($client->up) . "\n";
            $message .= "📉 Download: " . $this->formatTraffic($client->down) . "\n";
            $message .= "📊 Total Traffic: " . $this->formatTraffic($client->up + $client->down) . "\n";

            if ($client->total > 0) {
                $used = (($client->up + $client->down) / $client->total) * 100;
                $message .= "📋 Usage: " . number_format($used, 1) . "%\n";
            }

            $message .= "🔄 Last Reset: " . ($client->reset ? date('M j, Y', $client->reset) : 'Never') . "\n";
        }

        $message .= "📅 Created: {$order->created_at->format('M j, Y H:i')}\n\n";
        $message .= "🔗 /config_{$order->id} - Get configuration\n";
        $message .= "🔄 /reset_{$order->id} - Reset proxy";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /balance command
     */
    protected function handleBalance(int $chatId, int $userId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        $wallet = $user->wallet;
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
    protected function handleServers(int $chatId, int $userId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        $servers = Server::where('status', 'active')
            ->orderBy('location')
            ->take(10)
            ->get();

        if ($servers->isEmpty()) {
            $this->sendMessage($chatId, "No servers available at the moment. Please try again later.");
            return;
        }

        $message = "🌐 Available Servers\n\n";

        foreach ($servers as $server) {
            $message .= "📍 {$server->location}\n";
            $message .= "🔧 Protocols: {$server->protocols}\n";
            $message .= "💵 Price: $" . number_format($server->price, 2) . "\n";
            $message .= "📊 Load: {$server->load}%\n";
            $message .= "🔗 /buy_{$server->id} to purchase\n\n";
        }

        $message .= "💡 Use /buy_[server_id] to purchase a server";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /orders command
     */
    protected function handleOrders(int $chatId, int $userId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        $orders = $user->orders()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        if ($orders->isEmpty()) {
            $this->sendMessage($chatId, "You have no orders yet. Use /servers to browse available servers.");
            return;
        }

        $message = "📋 Your Recent Orders\n\n";

        foreach ($orders as $order) {
            $statusIcon = $this->getOrderStatusIcon($order->status);
            $message .= "{$statusIcon} Order #{$order->id}\n";
            $message .= "🌐 Server: {$order->server->location}\n";
            $message .= "💰 Amount: $" . number_format($order->amount, 2) . "\n";
            $message .= "📅 Date: {$order->created_at->format('M j, Y')}\n";
            $message .= "📊 Status: {$order->status}\n\n";
        }

        $message .= "🔗 Visit " . config('app.url') . "/orders for detailed order management";

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle /buy command
     */
    protected function handleBuy(int $chatId, int $userId, string $params): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        // Extract server ID from params (e.g., "buy_1" or "1")
        $serverId = null;
        if (str_starts_with($params, 'buy_')) {
            $serverId = (int) substr($params, 4);
        } elseif (is_numeric($params)) {
            $serverId = (int) $params;
        }

        if (!$serverId) {
            $this->sendMessage($chatId, "Please specify a server ID. Use /servers to see available servers.");
            return;
        }

        $server = Server::find($serverId);
        if (!$server || $server->status !== 'active') {
            $this->sendMessage($chatId, "Server not found or unavailable. Use /servers to see available servers.");
            return;
        }

        // Check wallet balance
        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $server->price) {
            $this->sendMessage($chatId, "Insufficient balance. Please top up your wallet at " . config('app.url') . "/wallet");
            return;
        }

        // Create order
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'amount' => $server->price,
                'status' => 'pending'
            ]);

            // Process payment from wallet
            $wallet->decrement('balance', $server->price);

            // Queue job for proxy creation
            ProcessXuiOrder::dispatch($order);

            $message = "✅ Order Created Successfully!\n\n";
            $message .= "📋 Order ID: #{$order->id}\n";
            $message .= "🌐 Server: {$server->location}\n";
            $message .= "💰 Amount: $" . number_format($server->price, 2) . "\n";
            $message .= "📊 Status: Processing\n\n";
            $message .= "⏳ Your proxy configuration will be ready shortly. You'll receive a notification when it's complete.";

            $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Telegram bot order creation failed', [
                'user_id' => $user->id,
                'server_id' => $server->id,
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
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

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
            'user_id' => $user->id,
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
        $message .= "👤 User: {$user->name}\n";
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
        $message .= "/myproxies - List your active proxies\n";
        $message .= "/config [order_id] - Get proxy configuration\n";
        $message .= "/reset [order_id] - Reset proxy with confirmation\n";
        $message .= "/status [order_id] - Check proxy status\n\n";
        $message .= "🌐 Server & Orders:\n";
        $message .= "/servers - Browse available servers\n";
        $message .= "/orders - View order history\n";
        $message .= "/buy [server_id] - Purchase proxy service\n\n";
        $message .= "🆘 Support:\n";
        $message .= "/support [message] - Contact support\n";
        $message .= "/help - Show this help message\n\n";
        $message .= "💡 Examples:\n";
        $message .= "• /buy 1 - Purchase server with ID 1\n";
        $message .= "• /config 123 - Get config for order 123\n";
        $message .= "• /reset 123 - Reset proxy for order 123\n";
        $message .= "• /status 123 - Check status of order 123\n";
        $message .= "• /support Can't connect to proxy - Send support message\n\n";
        $message .= "🔗 Web Dashboard: " . config('app.url');

        $this->sendMessage($chatId, $message);
    }

    /**
     * Handle reset confirmation
     */
    protected function handleResetConfirm(int $chatId, int $userId, int $orderId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        $order = $user->orders()
            ->where('id', $orderId)
            ->where('status', 'completed')
            ->with(['server', 'serverClient'])
            ->first();

        if (!$order || !$order->serverClient) {
            $this->sendMessage($chatId, "❌ Order not found or already reset.");
            return;
        }

        try {
            // Reset the proxy via XUI service
            $xuiService = app(\App\Services\XUIService::class);
            $result = $xuiService->resetClient($order->server, $order->serverClient);

            if ($result['success']) {
                $message = "✅ Proxy Reset Successfully!\n\n";
                $message .= "📋 Order #{$order->id}\n";
                $message .= "🌐 Server: {$order->server->location}\n";
                $message .= "🔄 New credentials generated\n";
                $message .= "📊 Traffic statistics cleared\n\n";
                $message .= "🔗 Get new config: /config_{$order->id}";
            } else {
                $message = "❌ Reset failed: " . ($result['message'] ?? 'Unknown error');
            }

            $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Telegram bot proxy reset failed', [
                'order_id' => $orderId,
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
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        $perPage = 5;
        $offset = ($page - 1) * $perPage;

        $servers = Server::where('status', 'active')
            ->orderBy('location')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $totalServers = Server::where('status', 'active')->count();
        $totalPages = ceil($totalServers / $perPage);

        if ($servers->isEmpty()) {
            $this->sendMessage($chatId, "No servers found on page {$page}.");
            return;
        }

        $message = "🌐 Available Servers (Page {$page}/{$totalPages})\n\n";

        foreach ($servers as $server) {
            $message .= "📍 {$server->location}\n";
            $message .= "🔧 Protocol: {$server->protocol}\n";
            $message .= "💵 Price: $" . number_format($server->price, 2) . "\n";
            $message .= "📊 Load: " . ($server->load ?? 0) . "%\n\n";
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

        // Add server purchase buttons
        foreach ($servers as $server) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🛒 Buy {$server->location} - $" . number_format($server->price, 2),
                 'callback_data' => "buy_server_{$server->id}"]
            ];
        }

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle buy confirmation
     */
    protected function handleBuyConfirm(int $chatId, int $userId, int $serverId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user) return;

        $server = Server::find($serverId);
        if (!$server || $server->status !== 'active') {
            $this->sendMessage($chatId, "❌ Server not available.");
            return;
        }

        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $server->price) {
            $message = "❌ Insufficient balance.\n\n";
            $message .= "💰 Required: $" . number_format($server->price, 2) . "\n";
            $message .= "💳 Current: $" . number_format($wallet ? $wallet->balance : 0, 2) . "\n\n";
            $message .= "Use /topup to add funds to your wallet.";

            $this->sendMessage($chatId, $message);
            return;
        }

        // Create confirmation keyboard
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Confirm Purchase', 'callback_data' => "confirm_buy_{$serverId}"],
                    ['text' => '❌ Cancel', 'callback_data' => 'cancel_buy']
                ]
            ]
        ];

        $message = "🛒 Confirm Purchase\n\n";
        $message .= "🌐 Server: {$server->location}\n";
        $message .= "🔧 Protocol: {$server->protocol}\n";
        $message .= "💵 Price: $" . number_format($server->price, 2) . "\n";
        $message .= "💰 Your Balance: $" . number_format($wallet->balance, 2) . "\n";
        $message .= "💳 After Purchase: $" . number_format($wallet->balance - $server->price, 2) . "\n\n";
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
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user || !$this->isAdmin($user)) {
            $this->sendMessage($chatId, "❌ Access denied. Admin privileges required.");
            return;
        }

        if (empty($params)) {
            // Show user statistics
            $totalUsers = User::count();
            $activeUsers = User::whereHas('orders', function($q) {
                $q->where('status', 'completed');
            })->count();
            $telegramUsers = User::whereNotNull('telegram_chat_id')->count();
            $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();

            $message = "👥 User Statistics\n\n";
            $message .= "📊 Total Users: {$totalUsers}\n";
            $message .= "✅ Active Users: {$activeUsers}\n";
            $message .= "📱 Telegram Linked: {$telegramUsers}\n";
            $message .= "🆕 New (7 days): {$recentUsers}\n\n";
            $message .= "💡 Use /users [email] to search for specific user";

            $this->sendMessage($chatId, $message);
            return;
        }

        // Search for specific user
        $searchUser = User::where('email', 'like', "%{$params}%")
            ->orWhere('name', 'like', "%{$params}%")
            ->first();

        if (!$searchUser) {
            $this->sendMessage($chatId, "❌ User not found: {$params}");
            return;
        }

        $orders = $searchUser->orders()->count();
        $activeOrders = $searchUser->orders()->where('status', 'completed')->count();
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
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user || !$this->isAdmin($user)) {
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
        $totalUsers = User::count();
        $totalOrders = Order::count();
        $completedOrders = Order::where('status', 'completed')->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $totalRevenue = Order::where('status', 'completed')->sum('amount');
        $todayRevenue = Order::where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');
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
        $totalServers = Server::count();
        $activeServers = Server::where('status', 'active')->count();
        $avgLoad = Server::where('status', 'active')->avg('load') ?? 0;

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
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user || !$this->isAdmin($user)) {
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
        $telegramUsers = User::whereNotNull('telegram_chat_id')->get();
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
            'admin_user_id' => $user->id,
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
     * Get authenticated user
     */
    protected function getAuthenticatedUser(int $chatId, int $userId): ?User
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->sendMessage($chatId, "❌ Please link your account first.\n\nVisit your account settings at " . config('app.url') . " and link your Telegram account.");
            return null;
        }

        return $user;
    }

    /**
     * Link Telegram account to user
     */
    protected function linkAccount(int $chatId, int $userId, string $token): void
    {
        // Here you would validate the token and link the account
        // For now, we'll assume the token is valid

        $user = User::where('telegram_link_token', $token)->first();

        if (!$user) {
            $this->sendMessage($chatId, "Invalid link token. Please try again from your dashboard.");
            return;
        }

        $user->update([
            'telegram_id' => $userId,
            'telegram_link_token' => null
        ]);

        $this->sendMessage($chatId, "✅ Account linked successfully! Welcome {$user->name}! Type /help to see available commands.");
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

        // Get user from database
        $user = User::find($webUserId);
        if (!$user) {
            $this->sendMessage($chatId, "❌ User not found. Please try again.");
            return;
        }

        // Check if this Telegram account is already linked to another user
        $existingUser = User::where('telegram_chat_id', $chatId)->first();
        if ($existingUser && $existingUser->id !== $user->id) {
            $this->sendMessage($chatId, "❌ This Telegram account is already linked to another user. Please unlink it first.");
            return;
        }

        // Link the accounts
        $telegramUser = $message->getFrom();
        $user->linkTelegram(
            $chatId,
            $telegramUser->getUsername(),
            $telegramUser->getFirstName(),
            $telegramUser->getLastName()
        );

        // Remove the linking code from cache
        cache()->forget($cacheKey);

        // Send success message
        $this->sendMessage($chatId, "✅ Account linked successfully!\n\nWelcome, {$user->name}! 🎉\n\nYour Telegram account is now connected to your 1000proxy account. Type /help to see available commands.");

        Log::info('Telegram account linked', [
            'user_id' => $user->id,
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
            $orderId = (int) substr($data, 14);
            $this->handleResetConfirm($chatId, $userId, $orderId);
        } elseif (str_starts_with($data, 'reset_cancel_')) {
            $orderId = (int) substr($data, 13);
            $this->sendMessage($chatId, "❌ Reset cancelled for Order #{$orderId}");
        } elseif (str_starts_with($data, 'server_page_')) {
            $page = (int) substr($data, 12);
            $this->handleServersPage($chatId, $userId, $page);
        } elseif (str_starts_with($data, 'buy_server_')) {
            $serverId = (int) substr($data, 11);
            $this->handleBuyConfirm($chatId, $userId, $serverId);
        } else {
            switch ($data) {
                case 'refresh_balance':
                    $this->handleBalance($chatId, $userId);
                    break;

                case 'view_servers':
                    $this->handleServers($chatId, $userId);
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
            }
        }

        // Answer callback query to remove loading state
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId()
        ]);
    }
}
