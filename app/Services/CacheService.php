<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerClient;

class CacheService
{
    /**
     * Cache duration constants
     */
    const CACHE_DURATION_SHORT = 300; // 5 minutes
    const CACHE_DURATION_MEDIUM = 1800; // 30 minutes
    const CACHE_DURATION_LONG = 3600; // 1 hour
    const CACHE_DURATION_VERY_LONG = 86400; // 24 hours

    /**
     * Get active servers with caching
     */
    public function getActiveServers(): \Illuminate\Support\Collection
    {
        return Cache::remember('active_servers', self::CACHE_DURATION_MEDIUM, function () {
            return Server::where('is_active', true)
                ->with(['brand', 'category'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get server plans with caching
     */
    public function getServerPlans(): \Illuminate\Support\Collection
    {
        return Cache::remember('server_plans', self::CACHE_DURATION_MEDIUM, function () {
            return ServerPlan::where('is_active', true)
                ->with(['brand', 'category'])
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get server statistics with caching
     */
    public function getServerStats(int $serverId): array
    {
        $cacheKey = "server_stats_{$serverId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($serverId) {
            return [
                'total_clients' => ServerClient::where('server_id', $serverId)->count(),
                'active_clients' => ServerClient::where('server_id', $serverId)
                    ->where('is_active', true)
                    ->count(),
                'total_bandwidth' => ServerClient::where('server_id', $serverId)
                    ->sum('bandwidth_used'),
                'last_updated' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get user's server clients with caching
     */
    public function getUserServerClients(int $userId): \Illuminate\Support\Collection
    {
        $cacheKey = "user_server_clients_{$userId}";

        return Cache::remember($cacheKey, self::CACHE_DURATION_SHORT, function () use ($userId) {
            return ServerClient::where('user_id', $userId)
                ->with(['server', 'serverPlan', 'order'])
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('dashboard_stats', self::CACHE_DURATION_SHORT, function () {
            return [
                'total_users' => DB::table('users')->count(),
                'active_servers' => DB::table('servers')->where('is_active', true)->count(),
                'total_orders' => DB::table('orders')->count(),
                'revenue_today' => DB::table('orders')
                    ->where('payment_status', 'paid')
                    ->whereDate('created_at', today())
                    ->sum('grand_amount'),
                'revenue_this_month' => DB::table('orders')
                    ->where('payment_status', 'paid')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('grand_amount'),
                'active_clients' => DB::table('server_clients')
                    ->where('is_active', true)
                    ->count(),
            ];
        });
    }

    /**
     * Cache server configuration
     */
    public function cacheServerConfig(int $serverId, array $config): void
    {
        $cacheKey = "server_config_{$serverId}";
        Cache::put($cacheKey, $config, self::CACHE_DURATION_LONG);
    }

    /**
     * Get cached server configuration
     */
    public function getServerConfig(int $serverId): ?array
    {
        $cacheKey = "server_config_{$serverId}";
        return Cache::get($cacheKey);
    }

    /**
     * Invalidate user-related caches
     */
    public function invalidateUserCaches(int $userId): void
    {
        $cacheKeys = [
            "user_server_clients_{$userId}",
            "user_orders_{$userId}",
            "user_profile_{$userId}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidate server-related caches
     */
    public function invalidateServerCaches(int $serverId): void
    {
        $cacheKeys = [
            "server_stats_{$serverId}",
            "server_config_{$serverId}",
            'active_servers',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidate all dashboard caches
     */
    public function invalidateDashboardCaches(): void
    {
        $cacheKeys = [
            'dashboard_stats',
            'active_servers',
            'server_plans',
            'payment_methods',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Cache currency exchange rates
     */
    public function cacheCurrencyRates(array $rates): void
    {
        Cache::put('currency_rates', $rates, self::CACHE_DURATION_LONG);
    }

    /**
     * Get cached currency exchange rates
     */
    public function getCurrencyRates(): ?array
    {
        return Cache::get('currency_rates');
    }

    /**
     * Cache XUI panel session
     */
    public function cacheXuiSession(int $serverId, string $sessionCookie): void
    {
        $cacheKey = "xui_session_{$serverId}";
        Cache::put($cacheKey, $sessionCookie, self::CACHE_DURATION_MEDIUM);
    }

    /**
     * Get cached XUI panel session
     */
    public function getXuiSession(int $serverId): ?string
    {
        $cacheKey = "xui_session_{$serverId}";
        return Cache::get($cacheKey);
    }

    /**
     * Cache client traffic statistics
     */
    public function cacheClientTraffic(string $clientUuid, array $traffic): void
    {
        $cacheKey = "client_traffic_{$clientUuid}";
        Cache::put($cacheKey, $traffic, self::CACHE_DURATION_SHORT);
    }

    /**
     * Get cached client traffic statistics
     */
    public function getClientTraffic(string $clientUuid): ?array
    {
        $cacheKey = "client_traffic_{$clientUuid}";
        return Cache::get($cacheKey);
    }

    /**
     * Warm up critical caches
     */
    public function warmUpCaches(): void
    {
        try {
            Log::info('Starting cache warmup');

            // Warm up active servers
            $this->getActiveServers();

            // Warm up server plans
            $this->getServerPlans();

            // Warm up dashboard stats
            $this->getDashboardStats();

            // Warm up server stats for all active servers
            $servers = Server::where('is_active', true)->pluck('id');
            foreach ($servers as $serverId) {
                $this->getServerStats($serverId);
            }

            Log::info('Cache warmup completed');
        } catch (\Exception $e) {
            Log::error('Cache warmup failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear all application caches
     */
    public function clearAllCaches(): void
    {
        try {
            Cache::flush();
            Log::info('All caches cleared');
        } catch (\Exception $e) {
            Log::error('Failed to clear caches', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cache_size' => 0,
            'memory_usage' => 0,
        ];

        try {
            // This would depend on your cache driver
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                $info = $redis->info('memory');
                $stats['memory_usage'] = $info['used_memory_human'] ?? 'N/A';
                $stats['cache_size'] = $redis->dbsize();
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', ['error' => $e->getMessage()]);
        }

        return $stats;
    }
}
