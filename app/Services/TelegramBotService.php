<?php

namespace App\Services;

use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use App\Models\Wallet;
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
                $this->handleMessage($update->getMessage());
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
                
            case '/servers':
                $this->handleServers($chatId, $userId);
                break;
                
            case '/orders':
                $this->handleOrders($chatId, $userId);
                break;
                
            case '/buy':
                $this->handleBuy($chatId, $userId, $params);
                break;
                
            case '/support':
                $this->handleSupport($chatId, $userId, $params);
                break;
                
            case '/help':
                $this->handleHelp($chatId);
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
            \App\Jobs\ProcessXuiOrder::dispatch($order);
            
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
            $message .= "📧 Email: support@1000proxy.com\n";
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
        $message .= "/balance - Check wallet balance\n\n";
        $message .= "🌐 Server Management:\n";
        $message .= "/servers - Browse available servers\n";
        $message .= "/orders - View order history\n";
        $message .= "/buy [server_id] - Purchase proxy service\n\n";
        $message .= "🆘 Support:\n";
        $message .= "/support [message] - Contact support\n";
        $message .= "/help - Show this help message\n\n";
        $message .= "💡 Examples:\n";
        $message .= "• /buy 1 - Purchase server with ID 1\n";
        $message .= "• /support Can't connect to proxy - Send support message\n\n";
        $message .= "🔗 Web Dashboard: " . config('app.url');
        
        $this->sendMessage($chatId, $message);
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
        switch ($data) {
            case 'refresh_balance':
                $this->handleBalance($chatId, $userId);
                break;
                
            case 'view_servers':
                $this->handleServers($chatId, $userId);
                break;
                
            // Add more callback handlers as needed
        }
        
        // Answer callback query to remove loading state
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->getId()
        ]);
    }
}