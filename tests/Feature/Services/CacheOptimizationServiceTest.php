<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CacheOptimizationService;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheOptimizationServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private CacheOptimizationService $cacheService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(CacheOptimizationService::class);
    }
    
    public function test_cache_server_data_stores_data_with_prefix()
    {
    $testData = ['server_id' => 1, 'status' => 'up'];
        
        $result = $this->cacheService->cacheServerData('test_server', $testData);
        
        $this->assertTrue($result);
        $this->assertEquals($testData, Cache::get('server:test_server'));
    }
    
    public function test_get_cached_server_data_retrieves_data()
    {
    $testData = ['server_id' => 1, 'status' => 'up'];
        Cache::put('server:test_server', $testData, 300);
        
        $result = $this->cacheService->getCachedServerData('test_server');
        
        $this->assertEquals($testData, $result);
    }
    
    public function test_cache_user_data_stores_data_with_user_prefix()
    {
        $userId = 123;
        $testData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        
        $result = $this->cacheService->cacheUserData($userId, 'profile', $testData);
        
        $this->assertTrue($result);
        $this->assertEquals($testData, Cache::get('user:123:profile'));
    }
    
    public function test_get_cached_user_data_retrieves_data()
    {
        $userId = 123;
        $testData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        Cache::put('user:123:profile', $testData, 300);
        
        $result = $this->cacheService->getCachedUserData($userId, 'profile');
        
        $this->assertEquals($testData, $result);
    }
    
    public function test_cache_analytics_data_uses_analytics_store()
    {
        $testData = ['revenue' => 5000, 'orders' => 100];
        
        Cache::shouldReceive('store')
            ->with('redis_analytics')
            ->once()
            ->andReturnSelf();
        
        Cache::shouldReceive('put')
            ->with('analytics:monthly_revenue', $testData, CacheOptimizationService::ANALYTICS_TTL)
            ->once()
            ->andReturn(true);
        
        $result = $this->cacheService->cacheAnalyticsData('monthly_revenue', $testData);
        
        $this->assertTrue($result);
    }
    
    public function test_cache_real_time_data_uses_short_ttl()
    {
        $testData = ['active_users' => 50, 'current_orders' => 5];
        
        Cache::shouldReceive('put')
            ->with('realtime:user_activity', $testData, CacheOptimizationService::SHORT_TTL)
            ->once()
            ->andReturn(true);
        
        $result = $this->cacheService->cacheRealTimeData('user_activity', $testData);
        
        $this->assertTrue($result);
    }
    
    public function test_cache_with_tags_stores_data()
    {
        $tags = ['servers', 'active'];
        $testData = ['server_list' => [1, 2, 3]];
        
        Cache::shouldReceive('tags')
            ->with($tags)
            ->once()
            ->andReturnSelf();
        
        Cache::shouldReceive('put')
            ->with('active_servers', $testData, CacheOptimizationService::MEDIUM_TTL)
            ->once()
            ->andReturn(true);
        
        $result = $this->cacheService->cacheWithTags($tags, 'active_servers', $testData);
        
        $this->assertTrue($result);
    }
    
    public function test_flush_cache_by_tags_clears_tagged_cache()
    {
        $tags = ['servers', 'active'];
        
        Cache::shouldReceive('tags')
            ->with($tags)
            ->once()
            ->andReturnSelf();
        
        Cache::shouldReceive('flush')
            ->once();
        
        $result = $this->cacheService->flushCacheByTags($tags);
        
        $this->assertTrue($result);
    }
    
    public function test_warm_up_cache_caches_critical_data()
    {
        // Create test data
    $activeServers = Server::factory()->count(3)->create(['status' => 'up']);
        // IMPORTANT: Explicitly associate popular plans with existing servers to avoid implicit
        // Server::factory() relationship creation in ServerPlanFactory which inflated the
        // active server count (was 5 instead of expected 3 when two additional servers were created).
        $popularPlans = ServerPlan::factory()->count(2)->create([
            'is_popular' => true,
            // Reuse one existing server id (first) so no new servers are created
            'server_id' => $activeServers->first()->id,
        ]);
        $settings = Setting::factory()->count(5)->create();
    // no-op extra debug removed

        $result = $this->cacheService->warmUpCache();
        
        $this->assertTrue($result);
        
        // Verify that critical data is cached
        $cachedServers = Cache::get('server:active_servers');
        // DEBUG: dump cachedServers content for investigation
        if (is_iterable($cachedServers)) {
            echo "DEBUG-CACHED-SERVERS: count=" . (is_countable($cachedServers) ? count($cachedServers) : 'unknown') . "\n";
            foreach ($cachedServers as $cs) {
                if (is_object($cs) && property_exists($cs, 'id')) {
                    echo "CACHED_SERVER_OBJ id=" . $cs->id . "\n";
                } elseif (is_array($cs) && isset($cs['id'])) {
                    echo "CACHED_SERVER_ARR id=" . $cs['id'] . "\n";
                } else {
                    echo "CACHED_UNKNOWN: " . json_encode($cs) . "\n";
                }
            }
        } else {
            echo "DEBUG-CACHED-SERVERS: not iterable: " . json_encode($cachedServers) . "\n";
        }
        $cachedPlans = Cache::get('server:popular_plans');
        $cachedSettings = Cache::get('server:system_settings');
        
        $this->assertNotNull($cachedServers);
        $this->assertNotNull($cachedPlans);
        $this->assertNotNull($cachedSettings);
        
        $this->assertCount(3, $cachedServers);
        $this->assertCount(2, $cachedPlans);
        $this->assertCount(5, $cachedSettings);
    }
    
    public function test_get_cache_stats_returns_redis_info()
    {
        // Mock Redis connection
        Redis::shouldReceive('connection')
            ->once()
            ->andReturnSelf();
        
        Redis::shouldReceive('info')
            ->once()
            ->andReturn([
                'used_memory_human' => '10.5M',
                'connected_clients' => 5,
                'total_commands_processed' => 1000,
                'keyspace_hits' => 800,
                'keyspace_misses' => 200,
                'uptime_in_seconds' => 3600
            ]);
        
        $stats = $this->cacheService->getCacheStats();
        
        $this->assertIsArray($stats);
        $this->assertEquals('10.5M', $stats['memory_usage']);
        $this->assertEquals(5, $stats['connected_clients']);
        $this->assertEquals(1000, $stats['total_commands_processed']);
        $this->assertEquals('80%', $stats['hit_rate']);
    }
    
    public function test_calculate_hit_rate_handles_zero_division()
    {
        $reflection = new \ReflectionClass($this->cacheService);
        $method = $reflection->getMethod('calculateHitRate');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->cacheService, 0, 0);
        $this->assertEquals('0%', $result);
        
        $result = $method->invoke($this->cacheService, 80, 20);
        $this->assertEquals('80%', $result);
    }
    
    public function test_invalidate_user_cache_clears_user_specific_cache()
    {
        $userId = 123;
        
        Redis::shouldReceive('keys')
            ->with('user:123:*')
            ->once()
            ->andReturn(['user:123:profile', 'user:123:orders']);
        
        Redis::shouldReceive('del')
            ->with(['user:123:profile', 'user:123:orders'])
            ->once();
        
        $result = $this->cacheService->invalidateUserCache($userId);
        
        $this->assertTrue($result);
    }
    
    public function test_invalidate_server_cache_clears_server_cache()
    {
        Redis::shouldReceive('keys')
            ->with('server:*')
            ->once()
            ->andReturn(['server:active_servers', 'server:popular_plans']);
        
        Redis::shouldReceive('del')
            ->with(['server:active_servers', 'server:popular_plans'])
            ->once();
        
        $result = $this->cacheService->invalidateServerCache();
        
        $this->assertTrue($result);
    }
    
    public function test_cache_operations_handle_exceptions_gracefully()
    {
        // Test caching with exception
        Cache::shouldReceive('put')
            ->andThrow(new \Exception('Cache connection failed'));
        
        $result = $this->cacheService->cacheServerData('test', ['data' => 'value']);
        $this->assertFalse($result);
        
        // Test retrieval with exception
        Cache::shouldReceive('get')
            ->andThrow(new \Exception('Cache connection failed'));
        
        $result = $this->cacheService->getCachedServerData('test');
        $this->assertNull($result);
    }
    
    public function test_cleanup_expired_cache_sets_default_ttl()
    {
        Redis::shouldReceive('connection')
            ->once()
            ->andReturnSelf();
        
        Redis::shouldReceive('keys')
            ->with('*')
            ->once()
            ->andReturn(['key1', 'key2', 'key3']);
        
        Redis::shouldReceive('ttl')
            ->with('key1')
            ->once()
            ->andReturn(-1); // No TTL set
        
        Redis::shouldReceive('ttl')
            ->with('key2')
            ->once()
            ->andReturn(300); // TTL already set
        
        Redis::shouldReceive('ttl')
            ->with('key3')
            ->once()
            ->andReturn(-1); // No TTL set
        
        Redis::shouldReceive('expire')
            ->with('key1', CacheOptimizationService::DAILY_TTL)
            ->once();
        
        Redis::shouldReceive('expire')
            ->with('key3', CacheOptimizationService::DAILY_TTL)
            ->once();
        
        $result = $this->cacheService->cleanupExpiredCache();
        
        $this->assertTrue($result);
    }
}
