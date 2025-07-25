<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Server;
use App\Models\Order;
use Carbon\Carbon;

class MobileAPIService
{
    protected $config;
    protected $rateLimitingService;
    protected $pushNotificationService;

    public function __construct(APIRateLimitingService $rateLimitingService)
    {
        $this->rateLimitingService = $rateLimitingService;
        $this->config = Config::get('mobile_api', []);
    }

    /**
     * Authenticate mobile device and user
     */
    public function authenticateDevice(Request $request): array
    {
        $credentials = $request->only(['email', 'password']);
        $deviceId = $request->header('X-Device-ID');
        $deviceInfo = $this->extractDeviceInfo($request);

        // Validate device ID
        if (!$deviceId || strlen($deviceId) < 10) {
            return [
                'success' => false,
                'error' => 'Invalid or missing device ID',
                'error_code' => 'INVALID_DEVICE_ID'
            ];
        }

        // Attempt authentication
        if (!Auth::attempt($credentials)) {
            return [
                'success' => false,
                'error' => 'Invalid credentials',
                'error_code' => 'AUTHENTICATION_FAILED'
            ];
        }

        $user = Auth::user();

        // Ensure we have a Customer, not a User
        if (!$user instanceof Customer) {
            // Try to find Customer by email
            $customer = Customer::where('email', $user->email)->first();
            if (!$customer) {
                return [
                    'success' => false,
                    'error' => 'Customer account not found',
                    'error_code' => 'CUSTOMER_NOT_FOUND'
                ];
            }
            $user = $customer;
        }

        // Create JWT token with device information
        $token = $user->createToken('mobile_device', ['mobile:access'], now()->addDays(30));

        // Register/update device
        $this->registerDevice($user, $deviceId, $deviceInfo);

        // Log successful authentication
        Log::info('Mobile device authenticated', [
            'user_id' => $user->id,
            'device_id' => substr($deviceId, 0, 8) . '***',
            'device_type' => $deviceInfo['platform']
        ]);

        return [
            'success' => true,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => 2592000, // 30 days
            'user' => $this->formatUserForMobile($user),
            'device_registered' => true
        ];
    }

    /**
     * Register mobile device for push notifications and tracking
     */
    public function registerDevice(Customer $user, string $deviceId, array $deviceInfo): void
    {
        $deviceData = [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'platform' => $deviceInfo['platform'],
            'app_version' => $deviceInfo['app_version'],
            'os_version' => $deviceInfo['os_version'],
            'device_model' => $deviceInfo['device_model'],
            'push_token' => $deviceInfo['push_token'] ?? null,
            'last_active' => now(),
            'registered_at' => now()
        ];

        // Store device information in cache and database
        Cache::put("mobile_device:{$deviceId}", $deviceData, now()->addDays(90));

        // Update user's device list
        $userDevices = Cache::get("user_devices:{$user->id}", []);
        $userDevices[$deviceId] = $deviceData;
        Cache::put("user_devices:{$user->id}", $userDevices, now()->addDays(90));
    }

    /**
     * Get mobile-optimized server list
     */
    public function getServersForMobile(Request $request): array
    {
        $page = (int) $request->get('page', 1);
        $perPage = min((int) $request->get('per_page', 20), 50); // Limit for mobile
        $filters = $request->only(['country', 'category', 'brand', 'location']);
        $search = $request->get('search');

        // Build query with mobile optimizations
        $query = Server::query()
            ->with(['brand:id,name,logo_url', 'category:id,name,color'])
            ->where('is_active', true)
            ->select([
                'id', 'name', 'location', 'country_code', 'host', 'port',
                'health_status', 'cpu_usage', 'memory_usage', 'client_count',
                'max_clients', 'brand_id', 'category_id', 'created_at'
            ]);

        // Apply filters
        if (!empty($filters['country'])) {
            $query->where('country_code', $filters['country']);
        }

        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (!empty($filters['brand'])) {
            $query->where('brand_id', $filters['brand']);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Apply mobile-specific sorting (prioritize healthy servers)
        $query->orderByRaw("
            CASE health_status
                WHEN 'healthy' THEN 1
                WHEN 'warning' THEN 2
                WHEN 'critical' THEN 3
                ELSE 4
            END
        ")->orderBy('cpu_usage', 'asc');

        // Paginate
        $servers = $query->paginate($perPage, ['*'], 'page', $page);

        // Format for mobile consumption
        $mobileServers = $servers->map(function ($server) {
            return $this->formatServerForMobile($server);
        });

        return [
            'data' => $mobileServers,
            'meta' => [
                'current_page' => $servers->currentPage(),
                'last_page' => $servers->lastPage(),
                'per_page' => $servers->perPage(),
                'total' => $servers->total(),
                'has_more' => $servers->hasMorePages()
            ],
            'filters_applied' => array_filter($filters),
            'cache_duration' => 300 // 5 minutes
        ];
    }

    /**
     * Get mobile-optimized order history
     */
    public function getOrdersForMobile(Request $request): array
    {
        $user = $request->user();
        $page = (int) $request->get('page', 1);
        $perPage = min((int) $request->get('per_page', 15), 30);
        $status = $request->get('status');

        $query = Order::query()
            ->with(['items.server:id,name,location', 'items.serverPlan:id,name,duration'])
            ->where('customer_id', $user->id)
            ->select([
                'id', 'order_number', 'total_amount', 'status', 'payment_status',
                'payment_method', 'expires_at', 'created_at', 'updated_at'
            ]);

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy('created_at', 'desc');

        $orders = $query->paginate($perPage, ['*'], 'page', $page);

        $mobileOrders = $orders->map(function ($order) {
            return $this->formatOrderForMobile($order);
        });

        return [
            'data' => $mobileOrders,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'has_more' => $orders->hasMorePages()
            ],
            'summary' => $this->getOrderSummaryForMobile($user)
        ];
    }

    /**
     * Get mobile-optimized user profile
     */
    public function getUserProfileForMobile(Request $request): array
    {
        $user = $request->user();
        $deviceId = $request->header('X-Device-ID');

        return [
            'user' => $this->formatUserForMobile($user),
            'device' => $this->getDeviceInfo($deviceId),
            'statistics' => $this->getUserStatistics($user),
            'preferences' => $this->getUserPreferences($user),
            'security' => $this->getSecurityStatus($user)
        ];
    }

    /**
     * Update user profile from mobile
     */
    public function updateUserProfileFromMobile(Request $request): array
    {
        $user = $request->user();
        $data = $request->only(['name', 'telegram_username', 'preferences']);

        try {
            // Update basic profile information
            if (isset($data['name'])) {
                $user->update(['name' => $data['name']]);
            }

            if (isset($data['telegram_username'])) {
                $user->update(['telegram_username' => $data['telegram_username']]);
            }

            // Update preferences
            if (isset($data['preferences'])) {
                $this->updateUserPreferences($user, $data['preferences']);
            }

            return [
                'success' => true,
                'user' => $this->formatUserForMobile($user->fresh()),
                'message' => 'Profile updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Mobile profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update profile',
                'error_code' => 'UPDATE_FAILED'
            ];
        }
    }

    /**
     * Handle mobile push notification registration
     */
    public function registerPushNotifications(Request $request): array
    {
        $deviceId = $request->header('X-Device-ID');
        $pushToken = $request->input('push_token');
        $preferences = $request->input('preferences', []);

        if (!$deviceId || !$pushToken) {
            return [
                'success' => false,
                'error' => 'Device ID and push token are required',
                'error_code' => 'MISSING_REQUIRED_DATA'
            ];
        }

        try {
            // Update device with push token
            $deviceData = Cache::get("mobile_device:{$deviceId}", []);
            $deviceData['push_token'] = $pushToken;
            $deviceData['notification_preferences'] = $preferences;
            $deviceData['push_registered_at'] = now();

            Cache::put("mobile_device:{$deviceId}", $deviceData, now()->addDays(90));

            // Register with push notification service
            $this->registerWithPushService($deviceId, $pushToken, $preferences);

            return [
                'success' => true,
                'message' => 'Push notifications registered successfully',
                'preferences' => $preferences
            ];
        } catch (\Exception $e) {
            Log::error('Push notification registration failed', [
                'device_id' => substr($deviceId, 0, 8) . '***',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to register push notifications',
                'error_code' => 'REGISTRATION_FAILED'
            ];
        }
    }

    /**
     * Get mobile-specific configuration
     */
    public function getMobileConfiguration(): array
    {
        return [
            'app_config' => [
                'current_version' => '2.1.0',
                'minimum_supported_version' => '2.0.0',
                'update_required' => false,
                'update_url' => [
                    'ios' => 'https://apps.apple.com/app/1000proxy',
                    'android' => 'https://play.google.com/store/apps/details?id=com.1000proxy.app'
                ]
            ],
            'api_config' => [
                'base_url' => url('/api/v2/mobile'),
                'websocket_url' => config('app.websocket_url'),
                'rate_limits' => [
                    'requests_per_minute' => 200,
                    'burst_limit' => 50
                ]
            ],
            'features' => [
                'push_notifications' => true,
                'biometric_auth' => true,
                'offline_mode' => true,
                'background_sync' => true,
                'qr_code_scanner' => true,
                'dark_mode' => true
            ],
            'cache_policies' => [
                'server_list' => 300, // 5 minutes
                'user_profile' => 900, // 15 minutes
                'order_history' => 600, // 10 minutes
                'configuration' => 3600 // 1 hour
            ]
        ];
    }

    /**
     * Handle mobile device synchronization
     */
    public function synchronizeDevice(Request $request): array
    {
        $deviceId = $request->header('X-Device-ID');
        $lastSync = $request->input('last_sync');
        $deviceData = $request->input('device_data', []);

        $syncData = [
            'timestamp' => now()->toISOString(),
            'updates_available' => [],
            'sync_required' => []
        ];

        // Check for user profile updates
        if ($this->hasUserProfileUpdates($request->user(), $lastSync)) {
            $syncData['updates_available']['profile'] = $this->formatUserForMobile($request->user());
        }

        // Check for order updates
        if ($this->hasOrderUpdates($request->user(), $lastSync)) {
            $syncData['updates_available']['orders'] = $this->getRecentOrderUpdates($request->user(), $lastSync);
        }

        // Check for server updates
        if ($this->hasServerUpdates($lastSync)) {
            $syncData['sync_required'][] = 'servers';
        }

        // Update device sync status
        $this->updateDeviceSyncStatus($deviceId, $deviceData);

        return $syncData;
    }

    /**
     * Format server data for mobile consumption
     */
    protected function formatServerForMobile($server): array
    {
        return [
            'id' => $server->id,
            'name' => $server->name,
            'location' => $server->location,
            'country_code' => $server->country_code,
            'health_status' => $server->health_status,
            'load_percentage' => round(($server->client_count / max($server->max_clients, 1)) * 100),
            'performance_score' => $this->calculatePerformanceScore($server),
            'brand' => [
                'id' => $server->brand?->id,
                'name' => $server->brand?->name,
                'logo_url' => $server->brand?->logo_url
            ],
            'category' => [
                'id' => $server->category?->id,
                'name' => $server->category?->name,
                'color' => $server->category?->color
            ],
            'status_indicator' => $this->getStatusIndicator($server->health_status),
            'quick_connect' => $this->canQuickConnect($server)
        ];
    }

    /**
     * Format order data for mobile consumption
     */
    protected function formatOrderForMobile($order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'total_amount' => (float) $order->total_amount,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'expires_at' => $order->expires_at?->toISOString(),
            'created_at' => $order->created_at->toISOString(),
            'items_count' => $order->items->count(),
            'primary_server' => $order->items->first()?->server?->name,
            'days_remaining' => $order->expires_at ?
                max(0, $order->expires_at->diffInDays(now())) : null,
            'can_download_config' => $order->status === 'completed',
            'status_color' => $this->getOrderStatusColor($order->status)
        ];
    }

    /**
     * Format user data for mobile consumption
     */
    protected function formatUserForMobile($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telegram_username' => $user->telegram_username,
            'wallet_balance' => (float) $user->wallet_balance,
            'status' => $user->status,
            'member_since' => $user->created_at->format('Y-m-d'),
            'total_orders' => $user->orders()->count(),
            'active_services' => $user->orders()->where('status', 'completed')
                ->where('expires_at', '>', now())->count(),
            'avatar_url' => $this->generateAvatarUrl($user)
        ];
    }

    /**
     * Extract device information from request headers
     */
    protected function extractDeviceInfo(Request $request): array
    {
        return [
            'platform' => $request->header('X-Device-Platform', 'unknown'),
            'app_version' => $request->header('X-App-Version', 'unknown'),
            'os_version' => $request->header('X-OS-Version', 'unknown'),
            'device_model' => $request->header('X-Device-Model', 'unknown'),
            'push_token' => $request->input('push_token'),
            'user_agent' => $request->userAgent(),
            'timezone' => $request->header('X-Timezone', 'UTC')
        ];
    }

    /**
     * Calculate server performance score
     */
    protected function calculatePerformanceScore($server): int
    {
        $cpuScore = max(0, 100 - $server->cpu_usage);
        $memoryScore = max(0, 100 - $server->memory_usage);
        $loadScore = max(0, 100 - (($server->client_count / max($server->max_clients, 1)) * 100));

        $healthScore = match($server->health_status) {
            'healthy' => 100,
            'warning' => 70,
            'critical' => 30,
            default => 0
        };

        return (int) (($cpuScore + $memoryScore + $loadScore + $healthScore) / 4);
    }

    /**
     * Get status indicator for UI
     */
    protected function getStatusIndicator(string $healthStatus): array
    {
        return match($healthStatus) {
            'healthy' => ['color' => '#10B981', 'icon' => 'check-circle', 'text' => 'Online'],
            'warning' => ['color' => '#F59E0B', 'icon' => 'exclamation-triangle', 'text' => 'Warning'],
            'critical' => ['color' => '#EF4444', 'icon' => 'x-circle', 'text' => 'Critical'],
            default => ['color' => '#6B7280', 'icon' => 'question-mark-circle', 'text' => 'Unknown']
        };
    }

    /**
     * Check if server can be quickly connected to
     */
    protected function canQuickConnect($server): bool
    {
        return $server->health_status === 'healthy' &&
               $server->cpu_usage < 80 &&
               $server->client_count < ($server->max_clients * 0.9);
    }

    /**
     * Get order status color for UI
     */
    protected function getOrderStatusColor(string $status): string
    {
        return match($status) {
            'completed' => '#10B981',
            'processing' => '#3B82F6',
            'pending' => '#F59E0B',
            'cancelled' => '#6B7280',
            'failed' => '#EF4444',
            default => '#6B7280'
        };
    }

    /**
     * Generate avatar URL for user
     */
    protected function generateAvatarUrl($user): string
    {
        return "https://ui-avatars.com/api/?name=" . urlencode($user->name) .
               "&background=0F172A&color=fff&size=200";
    }

    /**
     * Get user statistics for mobile dashboard
     */
    protected function getUserStatistics($user): array
    {
        return [
            'total_spent' => (float) $user->orders()->sum('total_amount'),
            'active_services' => $user->orders()->where('status', 'completed')
                ->where('expires_at', '>', now())->count(),
            'total_orders' => $user->orders()->count(),
            'success_rate' => $this->calculateSuccessRate($user),
            'member_for_days' => $user->created_at->diffInDays(now()),
            'last_order' => $user->orders()->latest()->first()?->created_at?->toISOString()
        ];
    }

    /**
     * Calculate user success rate
     */
    protected function calculateSuccessRate($user): float
    {
        $totalOrders = $user->orders()->count();
        if ($totalOrders === 0) return 0;

        $successfulOrders = $user->orders()->where('status', 'completed')->count();
        return round(($successfulOrders / $totalOrders) * 100, 1);
    }

    /**
     * Additional helper methods for mobile API functionality
     */
    protected function getUserPreferences($user): array
    {
        return Cache::get("user_preferences:{$user->id}", [
            'notifications' => [
                'order_updates' => true,
                'service_expiry' => true,
                'promotional' => false,
                'security_alerts' => true
            ],
            'app_settings' => [
                'dark_mode' => false,
                'biometric_auth' => false,
                'auto_sync' => true,
                'data_saver' => false
            ]
        ]);
    }

    protected function updateUserPreferences($user, array $preferences): void
    {
        Cache::put("user_preferences:{$user->id}", $preferences, now()->addDays(90));
    }

    protected function getSecurityStatus($user): array
    {
        return [
            'two_factor_enabled' => false, // Will be implemented later
            'last_password_change' => null,
            'active_sessions' => 1,
            'suspicious_activity' => false
        ];
    }

    protected function getDeviceInfo(string $deviceId): ?array
    {
        return Cache::get("mobile_device:{$deviceId}");
    }

    protected function getOrderSummaryForMobile($user): array
    {
        return [
            'total' => $user->orders()->count(),
            'completed' => $user->orders()->where('status', 'completed')->count(),
            'pending' => $user->orders()->where('status', 'pending')->count(),
            'processing' => $user->orders()->where('status', 'processing')->count()
        ];
    }

    protected function registerWithPushService(string $deviceId, string $pushToken, array $preferences): void
    {
        // Implementation would integrate with Firebase, APNs, etc.
        Log::info('Device registered for push notifications', [
            'device_id' => substr($deviceId, 0, 8) . '***',
            'preferences' => $preferences
        ]);
    }

    protected function hasUserProfileUpdates($user, ?string $lastSync): bool
    {
        if (!$lastSync) return true;
        return $user->updated_at > Carbon::parse($lastSync);
    }

    protected function hasOrderUpdates($user, ?string $lastSync): bool
    {
        if (!$lastSync) return true;
        return $user->orders()->where('updated_at', '>', Carbon::parse($lastSync))->exists();
    }

    protected function hasServerUpdates(?string $lastSync): bool
    {
        if (!$lastSync) return true;
        return Server::where('updated_at', '>', Carbon::parse($lastSync))->exists();
    }

    protected function getRecentOrderUpdates($user, string $lastSync): array
    {
        return $user->orders()
            ->where('updated_at', '>', Carbon::parse($lastSync))
            ->with(['items.server:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($order) => $this->formatOrderForMobile($order))
            ->toArray();
    }

    protected function updateDeviceSyncStatus(string $deviceId, array $deviceData): void
    {
        $existingData = Cache::get("mobile_device:{$deviceId}", []);
        $existingData['last_sync'] = now();
        $existingData['device_data'] = $deviceData;
        Cache::put("mobile_device:{$deviceId}", $existingData, now()->addDays(90));
    }
}
