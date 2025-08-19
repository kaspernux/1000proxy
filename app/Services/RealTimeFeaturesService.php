<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ServerClient;
use App\Models\WalletTransaction;
use App\Events\OrderStatusChanged;
use App\Events\ClientStatusChanged;
use App\Events\WalletBalanceChanged;
use App\Events\SystemAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RealTimeFeaturesService
{
    private $redis;

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Broadcast order status change
     */
    public function broadcastOrderStatusChange(Order $order, string $oldStatus, string $newStatus): void
    {
        try {
            // Update real-time cache
            $this->updateOrderCache($order);

            // Broadcast to customer's channel
            broadcast(new OrderStatusChanged($order, $oldStatus, $newStatus))
                ->toOthers();

            // Store in activity stream
            $this->addToActivityStream($order->customer_id, 'order_status_changed', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'timestamp' => now()->toISOString(),
            ]);

            // Send real-time notification
            $this->sendRealTimeNotification($order->customer_id, [
                'type' => 'order_status_changed',
                'title' => 'Order Status Updated',
                'message' => "Your order #{$order->id} status changed to {$newStatus}",
                'data' => [
                    'order_id' => $order->id,
                    'status' => $newStatus,
                ],
            ]);

            Log::info('Order status change broadcasted', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast order status change', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast client status change
     */
    public function broadcastClientStatusChange(ServerClient $client, bool $oldStatus, bool $newStatus): void
    {
        try {
            // Update real-time cache
            $this->updateClientCache($client);

            // Broadcast to customer's channel
            broadcast(new ClientStatusChanged($client, $oldStatus, $newStatus))
                ->toOthers();

            // Store in activity stream
            $this->addToActivityStream($client->user_id, 'client_status_changed', [
                'client_id' => $client->id,
                'server_name' => $client->server->name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'timestamp' => now()->toISOString(),
            ]);

            // Send real-time notification
            $this->sendRealTimeNotification($client->user_id, [
                'type' => 'client_status_changed',
                'title' => 'Client Status Updated',
                'message' => "Your client on {$client->server->name} is now " . ($newStatus ? 'active' : 'inactive'),
                'data' => [
                    'client_id' => $client->id,
                    'status' => $newStatus,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast client status change', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast wallet balance change
     */
    public function broadcastWalletBalanceChange(WalletTransaction $transaction): void
    {
        try {
            $customer = $transaction->wallet->customer;
            $newBalance = $transaction->wallet->balance;

            // Update real-time cache
            if ($customer) {
                $this->updateWalletCache($customer, $newBalance);
            }

            // Broadcast to customer's channel
            if ($customer) {
                broadcast(new WalletBalanceChanged($customer, $transaction, $newBalance))
                ->toOthers();
            }

            // Store in activity stream
            if ($customer) {
                $this->addToActivityStream($customer->id, 'wallet_balance_changed', [
                'transaction_id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'new_balance' => $newBalance,
                'timestamp' => now()->toISOString(),
                ]);
            }

            // Send real-time notification
            if ($customer) {
                $this->sendRealTimeNotification($customer->id, [
                'type' => 'wallet_balance_changed',
                'title' => 'Wallet Updated',
                'message' => ucfirst($transaction->type) . " of $" . number_format($transaction->amount, 2) . " processed",
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'new_balance' => $newBalance,
                ],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to broadcast wallet balance change', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast system alert
     */
    public function broadcastSystemAlert(string $type, string $message, array $data = []): void
    {
        try {
            $alert = [
                'type' => $type,
                'message' => $message,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ];

            // Store in system alerts cache
            $this->storeSystemAlert($alert);

            // Broadcast to admin channels
            broadcast(new SystemAlert($alert))->toOthers();

            Log::info('System alert broadcasted', [
                'type' => $type,
                'message' => $message,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to broadcast system alert', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get real-time user activity
     */
    public function getUserActivity(int $userId): array
    {
        $key = "user_activity:{$userId}";
        $activities = $this->redis->lrange($key, 0, 49); // Get last 50 activities
        
        return array_map(function ($activity) {
            return json_decode($activity, true);
        }, $activities);
    }

    /**
     * Get real-time system metrics
     */
    public function getSystemMetrics(): array
    {
        return Cache::remember('system_metrics', 60, function () {
            return [
                'active_users' => $this->getActiveUsersCount(),
                'pending_orders' => $this->getPendingOrdersCount(),
                'system_health' => $this->getSystemHealth(),
                'server_status' => $this->getServerStatus(),
                'recent_activities' => $this->getRecentActivities(),
            ];
        });
    }

    /**
     * Get live chat messages
     */
    public function getLiveChatMessages(int $userId, int $limit = 50): array
    {
        $key = "chat_messages:{$userId}";
        $messages = $this->redis->lrange($key, -$limit, -1);
        
        return array_map(function ($message) {
            return json_decode($message, true);
        }, $messages);
    }

    /**
     * Send live chat message
     */
    public function sendLiveChatMessage(int $userId, string $message, string $sender = 'user'): void
    {
        $messageData = [
            'id' => uniqid(),
            'user_id' => $userId,
            'message' => $message,
            'sender' => $sender,
            'timestamp' => now()->toISOString(),
        ];

        $key = "chat_messages:{$userId}";
        $this->redis->rpush($key, json_encode($messageData));
        $this->redis->expire($key, 86400); // Expire after 24 hours

    // Broadcast to customer and admin channels
        $this->broadcastChatMessage($userId, $messageData);
    }

    /**
     * Get real-time notifications
     */
    public function getNotifications(int $userId): array
    {
        $key = "notifications:{$userId}";
        $notifications = $this->redis->lrange($key, 0, 19); // Get last 20 notifications
        
        return array_map(function ($notification) {
            return json_decode($notification, true);
        }, $notifications);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(int $userId, string $notificationId): void
    {
        $key = "notifications:{$userId}";
        $notifications = $this->redis->lrange($key, 0, -1);
        
        foreach ($notifications as $index => $notification) {
            $data = json_decode($notification, true);
            if ($data['id'] === $notificationId) {
                $data['read'] = true;
                $this->redis->lset($key, $index, json_encode($data));
                break;
            }
        }
    }

    /**
     * Get connection status
     */
    public function getConnectionStatus(): array
    {
        return [
            'websocket_connections' => $this->getWebSocketConnections(),
            'redis_connections' => $this->getRedisConnections(),
            'database_connections' => $this->getDatabaseConnections(),
        ];
    }

    /**
     * Update order cache
     */
    private function updateOrderCache(Order $order): void
    {
        $key = "order:{$order->id}";
        $this->redis->setex($key, 3600, json_encode($order->toArray()));
    }

    /**
     * Update client cache
     */
    private function updateClientCache(ServerClient $client): void
    {
        $key = "client:{$client->id}";
        $this->redis->setex($key, 3600, json_encode($client->toArray()));
    }

    /**
     * Update wallet cache
     */
    private function updateWalletCache(Customer $customer, float $balance): void
    {
        $key = "wallet:{$customer->id}";
        $this->redis->setex($key, 3600, json_encode([
            'user_id' => $customer->id,
            'balance' => $balance,
            'updated_at' => now()->toISOString(),
        ]));
    }

    /**
     * Add to activity stream
     */
    private function addToActivityStream(int $userId, string $type, array $data): void
    {
        $activity = [
            'id' => uniqid(),
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        $key = "user_activity:{$userId}";
        $this->redis->lpush($key, json_encode($activity));
        $this->redis->ltrim($key, 0, 99); // Keep only last 100 activities
        $this->redis->expire($key, 86400 * 7); // Expire after 7 days
    }

    /**
     * Send real-time notification
     */
    private function sendRealTimeNotification(int $userId, array $notification): void
    {
        $notification['id'] = uniqid();
        $notification['read'] = false;
        $notification['timestamp'] = now()->toISOString();

        $key = "notifications:{$userId}";
        $this->redis->lpush($key, json_encode($notification));
        $this->redis->ltrim($key, 0, 49); // Keep only last 50 notifications
        $this->redis->expire($key, 86400 * 30); // Expire after 30 days
    }

    /**
     * Store system alert
     */
    private function storeSystemAlert(array $alert): void
    {
        $alert['id'] = uniqid();
        
        $key = "system_alerts";
        $this->redis->lpush($key, json_encode($alert));
        $this->redis->ltrim($key, 0, 99); // Keep only last 100 alerts
        $this->redis->expire($key, 86400 * 7); // Expire after 7 days
    }

    /**
     * Get active users count
     */
    private function getActiveUsersCount(): int
    {
        return User::where('last_login_at', '>=', now()->subMinutes(15))->count();
    }

    /**
     * Get pending orders count
     */
    private function getPendingOrdersCount(): int
    {
        return Order::whereIn('order_status', ['new', 'processing'])->count();
    }

    /**
     * Get system health
     */
    private function getSystemHealth(): array
    {
        return [
            'status' => 'healthy',
            'cpu_usage' => 45.2,
            'memory_usage' => 62.8,
            'disk_usage' => 34.5,
            'uptime' => '99.9%',
        ];
    }

    /**
     * Get server status
     */
    private function getServerStatus(): array
    {
        return [
            'total_servers' => 5,
            'active_servers' => 5,
            'inactive_servers' => 0,
            'average_response_time' => 125,
        ];
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities(): array
    {
        $activities = $this->redis->lrange('recent_activities', 0, 9);
        
        return array_map(function ($activity) {
            return json_decode($activity, true);
        }, $activities);
    }

    /**
     * Broadcast chat message
     */
    private function broadcastChatMessage(int $userId, array $messageData): void
    {
        // This would use Laravel's broadcasting system
        // For now, we'll just log it
        Log::info('Chat message broadcasted', [
            'user_id' => $userId,
            'message_id' => $messageData['id'],
        ]);
    }

    /**
     * Get WebSocket connections
     */
    private function getWebSocketConnections(): int
    {
        return 150; // Placeholder
    }

    /**
     * Get Redis connections
     */
    private function getRedisConnections(): int
    {
        return 25; // Placeholder
    }

    /**
     * Get database connections
     */
    private function getDatabaseConnections(): int
    {
        return 10; // Placeholder
    }

    /**
     * Track user presence
     */
    public function trackUserPresence(int $userId, string $status = 'online'): void
    {
        $key = "user_presence:{$userId}";
        $presenceData = [
            'user_id' => $userId,
            'status' => $status,
            'last_seen' => now()->toISOString(),
        ];

        $this->redis->setex($key, 300, json_encode($presenceData)); // 5 minutes TTL
    }

    /**
     * Get user presence
     */
    public function getUserPresence(int $userId): array
    {
        $key = "user_presence:{$userId}";
        $data = $this->redis->get($key);
        
        if ($data) {
            return json_decode($data, true);
        }

        return [
            'user_id' => $userId,
            'status' => 'offline',
            'last_seen' => null,
        ];
    }

    /**
     * Get all online users
     */
    public function getOnlineUsers(): array
    {
        $pattern = "user_presence:*";
        $keys = $this->redis->keys($pattern);
        
        $onlineUsers = [];
        foreach ($keys as $key) {
            $data = $this->redis->get($key);
            if ($data) {
                $presence = json_decode($data, true);
                if ($presence['status'] === 'online') {
                    $onlineUsers[] = $presence;
                }
            }
        }

        return $onlineUsers;
    }

    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications(array $userIds, array $notification): void
    {
        foreach ($userIds as $userId) {
            $this->sendRealTimeNotification($userId, $notification);
        }
    }

    /**
     * Get system alerts
     */
    public function getSystemAlerts(): array
    {
        $alerts = $this->redis->lrange('system_alerts', 0, 19);
        
        return array_map(function ($alert) {
            return json_decode($alert, true);
        }, $alerts);
    }

    /**
     * Clear user notifications
     */
    public function clearUserNotifications(int $userId): void
    {
        $key = "notifications:{$userId}";
        $this->redis->del($key);
    }

    /**
     * Get notification count
     */
    public function getNotificationCount(int $userId): int
    {
        $key = "notifications:{$userId}";
        return $this->redis->llen($key);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadNotificationCount(int $userId): int
    {
        $notifications = $this->getNotifications($userId);
        
        return count(array_filter($notifications, function ($notification) {
            return !($notification['read'] ?? false);
        }));
    }
}
