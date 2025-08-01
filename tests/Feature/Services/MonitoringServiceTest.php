<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\MonitoringService;
use App\Services\CacheOptimizationService;
use App\Services\QueueOptimizationService;
use App\Services\AdvancedAnalyticsService;
use App\Models\Server;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitoringServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private MonitoringService $monitoringService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monitoringService = app(MonitoringService::class);
    }
    
    public function test_run_health_check_returns_healthy_status()
    {
        // Create test data
        Server::factory()->create(['status' => 'active']);
        Order::factory()->create(['status' => 'completed']);
        User::factory()->create(['last_active_at' => now()]);
        
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertIsArray($healthStatus);
        $this->assertArrayHasKey('overall', $healthStatus);
        $this->assertArrayHasKey('timestamp', $healthStatus);
        $this->assertArrayHasKey('checks', $healthStatus);
        
        // Check that all health checks are present
        $expectedChecks = ['database', 'cache', 'queue', 'servers', 'application', 'storage'];
        foreach ($expectedChecks as $check) {
            $this->assertArrayHasKey($check, $healthStatus['checks']);
            $this->assertArrayHasKey('status', $healthStatus['checks'][$check]);
        }
    }
    
    public function test_database_health_check_detects_connection_issues()
    {
        // Temporarily disconnect from database
        DB::disconnect();
        
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertEquals('critical', $healthStatus['overall']);
        $this->assertEquals('critical', $healthStatus['checks']['database']['status']);
        $this->assertContains('Database connection failed', $healthStatus['checks']['database']['issues']);
    }
    
    public function test_server_health_check_detects_no_active_servers()
    {
        // Create only inactive servers
        Server::factory()->create(['status' => 'inactive']);
        Server::factory()->create(['status' => 'maintenance']);
        
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertEquals('critical', $healthStatus['overall']);
        $this->assertEquals('critical', $healthStatus['checks']['servers']['status']);
        $this->assertContains('No active servers available', $healthStatus['checks']['servers']['issues']);
    }
    
    public function test_server_health_check_detects_low_server_count()
    {
        // Create only 2 active servers (below threshold of 3)
        Server::factory()->count(2)->create(['status' => 'active']);
        
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertEquals('warning', $healthStatus['overall']);
        $this->assertEquals('warning', $healthStatus['checks']['servers']['status']);
        $this->assertContains('Low number of active servers', $healthStatus['checks']['servers']['issues']);
    }
    
    public function test_server_health_check_detects_high_capacity_servers()
    {
        // Create servers with high capacity usage
        Server::factory()->create([
            'status' => 'active',
            'current_clients' => 80,
            'max_clients' => 100
        ]);
        
        Server::factory()->create([
            'status' => 'active',
            'current_clients' => 90,
            'max_clients' => 100
        ]);
        
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertEquals('warning', $healthStatus['overall']);
        $this->assertEquals('warning', $healthStatus['checks']['servers']['status']);
        $this->assertContains('servers are near capacity', $healthStatus['checks']['servers']['issues'][0]);
    }
    
    public function test_application_health_check_detects_failed_orders()
    {
        // Create failed orders in the last hour
        Order::factory()->count(3)->create([
            'status' => 'failed',
            'created_at' => now()->subMinutes(30)
        ]);
        
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertEquals('warning', $healthStatus['overall']);
        $this->assertEquals('warning', $healthStatus['checks']['application']['status']);
        $this->assertContains('orders failed in the last hour', $healthStatus['checks']['application']['issues'][0]);
    }
    
    public function test_cache_health_check_returns_stats()
    {
        // Mock cache stats
        Cache::shouldReceive('get')
            ->andReturn(['hit_rate' => '85%', 'memory_usage' => '500MB']);
        
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertArrayHasKey('cache', $healthStatus['checks']);
        $this->assertArrayHasKey('hit_rate', $healthStatus['checks']['cache']);
        $this->assertArrayHasKey('memory_usage', $healthStatus['checks']['cache']);
    }
    
    public function test_storage_health_check_detects_write_permissions()
    {
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertArrayHasKey('storage', $healthStatus['checks']);
        $this->assertArrayHasKey('storage_writable', $healthStatus['checks']['storage']);
        $this->assertArrayHasKey('logs_writable', $healthStatus['checks']['storage']);
    }
    
    public function test_performance_metrics_returns_expected_structure()
    {
        $performanceMetrics = $this->monitoringService->getPerformanceMetrics();
        
        $this->assertIsArray($performanceMetrics);
        $this->assertArrayHasKey('response_times', $performanceMetrics);
        $this->assertArrayHasKey('throughput', $performanceMetrics);
        $this->assertArrayHasKey('error_rates', $performanceMetrics);
        $this->assertArrayHasKey('resource_usage', $performanceMetrics);
        $this->assertArrayHasKey('timestamp', $performanceMetrics);
    }
    
    public function test_health_check_caches_results()
    {
        // Mock cache service
        $cacheService = $this->createMock(CacheOptimizationService::class);
        $cacheService->expects($this->once())
            ->method('cacheRealTimeData')
            ->with('system_health', $this->anything(), 300);
        
        // Create monitoring service with mocked cache service
        $monitoringService = new MonitoringService(
            $cacheService,
            app(QueueOptimizationService::class),
            app(AdvancedAnalyticsService::class)
        );
        
        // Create test data for healthy system
        Server::factory()->create(['status' => 'active']);
        
        $monitoringService->runHealthCheck();
    }
    
    public function test_health_check_logs_critical_issues()
    {
        Log::shouldReceive('critical')
            ->once()
            ->with('System health critical', $this->anything());
        
        Log::shouldReceive('error')
            ->atLeast()
            ->once();
        
        // Create scenario with no active servers (critical issue)
        Server::factory()->create(['status' => 'inactive']);
        
        $this->monitoringService->runHealthCheck();
    }
    
    public function test_health_check_logs_warning_issues()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('System health warning', $this->anything());
        
        // Create scenario with low server count (warning issue)
        Server::factory()->count(2)->create(['status' => 'active']);
        
        $this->monitoringService->runHealthCheck();
    }
    
    public function test_health_check_handles_exceptions_gracefully()
    {
        // Mock database to throw exception
        DB::shouldReceive('connection')
            ->andThrow(new \Exception('Database connection failed'));
        
        Log::shouldReceive('error')
            ->once()
            ->with('Health check failed', $this->anything());
        
        $healthStatus = $this->monitoringService->runHealthCheck();
        
        $this->assertEquals('critical', $healthStatus['overall']);
        $this->assertArrayHasKey('error', $healthStatus);
    }
    
    public function test_overall_health_determination()
    {
        // Test critical status takes precedence
        $checks = [
            'database' => ['status' => 'healthy'],
            'cache' => ['status' => 'warning'],
            'servers' => ['status' => 'critical']
        ];
        
        $reflection = new \ReflectionClass($this->monitoringService);
        $method = $reflection->getMethod('determineOverallHealth');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->monitoringService, $checks);
        $this->assertEquals('critical', $result);
        
        // Test warning status
        $checks['servers']['status'] = 'warning';
        $result = $method->invoke($this->monitoringService, $checks);
        $this->assertEquals('warning', $result);
        
        // Test healthy status
        $checks['cache']['status'] = 'healthy';
        $result = $method->invoke($this->monitoringService, $checks);
        $this->assertEquals('healthy', $result);
    }
}
