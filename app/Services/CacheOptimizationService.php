<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CacheOptimizationService
{
    // Cache prefixes for different data types
    const SERVER_CACHE_PREFIX = 'server:';
    const USER_CACHE_PREFIX = 'user:';
    const ORDER_CACHE_PREFIX = 'order:';
    const ANALYTICS_CACHE_PREFIX = 'analytics:';
    const REAL_TIME_CACHE_PREFIX = 'realtime:';
    
    // Cache TTL constants (in seconds)
    const SHORT_TTL = 300; // 5 minutes
    const MEDIUM_TTL = 1800; // 30 minutes
    const LONG_TTL = 3600; // 1 hour
    const ANALYTICS_TTL = 14400; // 4 hours
    const DAILY_TTL = 86400; // 24 hours
    
    /**
     * Cache server data with appropriate TTL
     */
    public function cacheServerData(string $key, $data, int $ttl = self::MEDIUM_TTL): bool
    {
        try {
            return Cache::put(self::SERVER_CACHE_PREFIX . $key, $data, $ttl);
        } catch (\Exception $e) {
            Log::error('Failed to cache server data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cached server data
     */
    public function getCachedServerData(string $key)
    {
        try {
            return Cache::get(self::SERVER_CACHE_PREFIX . $key);
        } catch (\Exception $e) {
            Log::error('Failed to get cached server data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Cache user-specific data
     */
    public function cacheUserData(int $userId, string $key, $data, int $ttl = self::MEDIUM_TTL): bool
    {
        try {
            return Cache::put(self::USER_CACHE_PREFIX . $userId . ':' . $key, $data, $ttl);
        } catch (\Exception $e) {
            Log::error('Failed to cache user data', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cached user data
     */
    public function getCachedUserData(int $userId, string $key)
    {
        try {
            return Cache::get(self::USER_CACHE_PREFIX . $userId . ':' . $key);
        } catch (\Exception $e) {
            Log::error('Failed to get cached user data', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Cache analytics data with long TTL
     */
    public function cacheAnalyticsData(string $key, $data, int $ttl = self::ANALYTICS_TTL): bool
    {
        try {
            return Cache::store('redis_analytics')->put(self::ANALYTICS_CACHE_PREFIX . $key, $data, $ttl);
        } catch (\Exception $e) {
            Log::error('Failed to cache analytics data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cached analytics data
     */
    public function getCachedAnalyticsData(string $key)
    {
        try {
            return Cache::store('redis_analytics')->get(self::ANALYTICS_CACHE_PREFIX . $key);
        } catch (\Exception $e) {
            Log::error('Failed to get cached analytics data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Cache real-time data with short TTL
     */
    public function cacheRealTimeData(string $key, $data, int $ttl = self::SHORT_TTL): bool
    {
        try {
            return Cache::put(self::REAL_TIME_CACHE_PREFIX . $key, $data, $ttl);
        } catch (\Exception $e) {
            Log::error('Failed to cache real-time data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cached real-time data
     */
    public function getCachedRealTimeData(string $key)
    {
        try {
            return Cache::get(self::REAL_TIME_CACHE_PREFIX . $key);
        } catch (\Exception $e) {
            Log::error('Failed to get cached real-time data', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Invalidate user cache
     */
    public function invalidateUserCache(int $userId): bool
    {
        try {
            $pattern = self::USER_CACHE_PREFIX . $userId . ':*';
            return $this->invalidateCachePattern($pattern);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate user cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Invalidate server cache
     */
    public function invalidateServerCache(string $serverKey = null): bool
    {
        try {
            $pattern = $serverKey ? self::SERVER_CACHE_PREFIX . $serverKey : self::SERVER_CACHE_PREFIX . '*';
            return $this->invalidateCachePattern($pattern);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate server cache', [
                'server_key' => $serverKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Invalidate cache by pattern
     */
    private function invalidateCachePattern(string $pattern): bool
    {
        try {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Cache with tags for easy invalidation
     */
    public function cacheWithTags(array $tags, string $key, $data, int $ttl = self::MEDIUM_TTL): bool
    {
        try {
            return Cache::tags($tags)->put($key, $data, $ttl);
        } catch (\Exception $e) {
            Log::error('Failed to cache with tags', [
                'tags' => $tags,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cached data with tags
     */
    public function getCachedWithTags(array $tags, string $key)
    {
        try {
            return Cache::tags($tags)->get($key);
        } catch (\Exception $e) {
            Log::error('Failed to get cached data with tags', [
                'tags' => $tags,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Flush cache by tags
     */
    public function flushCacheByTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to flush cache by tags', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();
            
            return [
                'memory_usage' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 'N/A',
                'total_commands_processed' => $info['total_commands_processed'] ?? 'N/A',
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info['keyspace_hits'] ?? 0, $info['keyspace_misses'] ?? 0),
                'uptime' => $info['uptime_in_seconds'] ?? 'N/A',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Calculate cache hit rate
     */
    private function calculateHitRate(int $hits, int $misses): string
    {
        $total = $hits + $misses;
        if ($total === 0) {
            return '0%';
        }
        
        return round(($hits / $total) * 100, 2) . '%';
    }
    
    /**
     * Warm up cache with critical data
     */
    public function warmUpCache(): bool
    {
        try {
            // Cache active servers
            $activeServers = \App\Models\Server::where('status', 'active')->get();
            $this->cacheServerData('active_servers', $activeServers, self::LONG_TTL);
            
            // Cache popular server plans
            $popularPlans = \App\Models\ServerPlan::where('is_popular', true)->get();
            $this->cacheServerData('popular_plans', $popularPlans, self::LONG_TTL);
            
            // Cache system settings
            $settings = \App\Models\Setting::all()->keyBy('key');
            $this->cacheServerData('system_settings', $settings, self::DAILY_TTL);
            
            Log::info('Cache warmed up successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to warm up cache', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Clean up expired cache entries
     */
    public function cleanupExpiredCache(): bool
    {
        try {
            // This would typically be handled by Redis automatically
            // But we can implement custom cleanup logic here
            $redis = Redis::connection();
            
            // Get all keys with TTL
            $keys = $redis->keys('*');
            $expiredCount = 0;
            
            foreach ($keys as $key) {
                $ttl = $redis->ttl($key);
                if ($ttl === -1) { // Key exists but has no TTL
                    // Set default TTL for keys without expiration
                    $redis->expire($key, self::DAILY_TTL);
                }
            }
            
            Log::info('Cache cleanup completed', ['expired_count' => $expiredCount]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired cache', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
