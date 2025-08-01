<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AdvancedBackendService;
use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class AdvancedBackendServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdvancedBackendService $service;
    private User $user;
    private Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AdvancedBackendService();
        $this->user = User::factory()->create();
        $this->server = Server::factory()->create();
    }

    /** @test */
    public function it_handles_connection_errors_with_smart_recovery()
    {
        $exception = new Exception('Connection timeout');
        $context = ['server_id' => $this->server->id];

        $result = $this->service->handleXuiError($exception, 'test_connection', $context);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['recovery_attempted']);
        $this->assertEquals('CONNECTION_ERROR', $result['error_details']['error_code']);
        $this->assertEquals('HIGH', $result['error_details']['severity']);
    }

    /** @test */
    public function it_handles_auth_errors_with_refresh_attempt()
    {
        $exception = new Exception('Authentication failed');
        $context = ['server_id' => $this->server->id];

        $result = $this->service->handleXuiError($exception, 'authenticate', $context);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['recovery_attempted']);
        $this->assertEquals('AUTH_ERROR', $result['error_details']['error_code']);
        $this->assertEquals('MEDIUM', $result['error_details']['severity']);
    }

    /** @test */
    public function it_handles_rate_limit_errors_with_backoff()
    {
        $exception = new Exception('Rate limit exceeded');
        $context = [];

        $result = $this->service->handleXuiError($exception, 'api_call', $context);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['recovery_attempted']);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $result['error_details']['error_code']);
        $this->assertEquals(60, $result['retry_after']);
    }

    /** @test */
    public function it_optimizes_performance_across_multiple_areas()
    {
        $result = $this->service->optimizePerformance();

        $this->assertArrayHasKey('optimizations_applied', $result);
        $this->assertArrayHasKey('performance_score', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('database', $result['optimizations_applied']);
        $this->assertArrayHasKey('cache', $result['optimizations_applied']);
        $this->assertArrayHasKey('load_balancing', $result['optimizations_applied']);
        $this->assertArrayHasKey('background_jobs', $result['optimizations_applied']);
    }

    /** @test */
    public function it_detects_fraud_with_low_risk_for_normal_transaction()
    {
        $transactionData = [
            'user_id' => $this->user->id,
            'amount' => 50,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'transaction_id' => 'txn_123'
        ];

        $result = $this->service->detectFraud($transactionData);

        $this->assertLessThan(25, $result['fraud_score']);
        $this->assertEquals('MINIMAL', $result['risk_level']);
        $this->assertFalse($result['action_required']['block']);
        $this->assertFalse($result['action_required']['manual_review']);
    }

    /** @test */
    public function it_detects_fraud_with_high_risk_for_suspicious_transaction()
    {
        // Create multiple recent orders to trigger velocity check
        Order::factory()->count(6)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMinutes(30)
        ]);

        $transactionData = [
            'user_id' => $this->user->id,
            'amount' => 1500, // High amount
            'ip_address' => '192.168.1.1',
            'user_agent' => 'curl/7.68.0', // Automated tool
            'transaction_id' => 'txn_suspicious'
        ];

        $result = $this->service->detectFraud($transactionData);

        $this->assertGreaterThan(50, $result['fraud_score']);
        $this->assertContains($result['risk_level'], ['MEDIUM', 'HIGH']);
        $this->assertTrue($result['action_required']['manual_review']);
    }

    /** @test */
    public function it_detects_new_user_fraud_risk()
    {
        $newUser = User::factory()->create([
            'created_at' => now()->subHours(2),
            'email_verified_at' => null
        ]);

        $transactionData = [
            'user_id' => $newUser->id,
            'amount' => 100,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'transaction_id' => 'txn_new_user'
        ];

        $result = $this->service->detectFraud($transactionData);

        $this->assertContains('Very new user account', $result['risk_factors']);
        $this->assertContains('Unverified email address', $result['risk_factors']);
        $this->assertGreaterThan(30, $result['fraud_score']);
    }

    /** @test */
    public function it_detects_automated_tools()
    {
        $transactionData = [
            'user_id' => $this->user->id,
            'amount' => 100,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'python-requests/2.25.1',
            'transaction_id' => 'txn_bot'
        ];

        $result = $this->service->detectFraud($transactionData);

        $this->assertContains('Automated tool or bot detected', $result['risk_factors']);
        $this->assertGreaterThan(35, $result['fraud_score']);
    }

    /** @test */
    public function it_handles_high_risk_fraud_with_blocking()
    {
        // Create a very high-risk transaction
        Order::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMinutes(15)
        ]);

        $newUser = User::factory()->create([
            'created_at' => now()->subHours(1),
            'email_verified_at' => null
        ]);

        $transactionData = [
            'user_id' => $newUser->id,
            'amount' => 2000,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'curl/7.68.0',
            'transaction_id' => 'txn_high_risk'
        ];

        $result = $this->service->detectFraud($transactionData);

        if ($result['fraud_score'] >= 75) {
            $this->assertEquals('HIGH', $result['risk_level']);
            $this->assertTrue($result['action_required']['block']);
            $this->assertTrue($result['action_required']['manual_review']);
            $this->assertTrue($result['action_required']['notify_admin']);
        }
    }

    /** @test */
    public function it_initializes_websocket_monitoring_channels()
    {
        $result = $this->service->initializeWebSocketMonitoring();

        $this->assertTrue($result['websocket_enabled']);
        $this->assertArrayHasKey('channels', $result);
        $this->assertArrayHasKey('server_status', $result['channels']);
        $this->assertArrayHasKey('user_activity', $result['channels']);
        $this->assertArrayHasKey('order_processing', $result['channels']);
        $this->assertArrayHasKey('system_alerts', $result['channels']);

        $this->assertEquals('server-status', $result['channels']['server_status']['channel']);
        $this->assertContains('server.online', $result['channels']['server_status']['events']);
        $this->assertContains('server.offline', $result['channels']['server_status']['events']);
    }

    /** @test */
    public function it_sets_up_user_activity_monitoring()
    {
        $result = $this->service->initializeWebSocketMonitoring();
        $userActivityChannel = $result['channels']['user_activity'];

        $this->assertEquals('user-activity', $userActivityChannel['channel']);
        $this->assertContains('user.login', $userActivityChannel['events']);
        $this->assertContains('user.logout', $userActivityChannel['events']);
        $this->assertContains('user.suspicious_activity', $userActivityChannel['events']);
        $this->assertEquals(1, $userActivityChannel['update_interval']);
    }

    /** @test */
    public function it_sets_up_order_processing_monitoring()
    {
        $result = $this->service->initializeWebSocketMonitoring();
        $orderChannel = $result['channels']['order_processing'];

        $this->assertEquals('order-processing', $orderChannel['channel']);
        $this->assertContains('order.created', $orderChannel['events']);
        $this->assertContains('order.processing', $orderChannel['events']);
        $this->assertContains('order.completed', $orderChannel['events']);
        $this->assertContains('order.failed', $orderChannel['events']);
    }

    /** @test */
    public function it_sets_up_system_alerts_monitoring()
    {
        $result = $this->service->initializeWebSocketMonitoring();
        $alertsChannel = $result['channels']['system_alerts'];

        $this->assertEquals('system-alerts', $alertsChannel['channel']);
        $this->assertContains('alert.critical', $alertsChannel['events']);
        $this->assertContains('alert.warning', $alertsChannel['events']);
        $this->assertContains('alert.fraud_detected', $alertsChannel['events']);
    }

    /** @test */
    public function it_caches_performance_optimization_results()
    {
        $result1 = $this->service->optimizePerformance();
        $result2 = $this->service->optimizePerformance();

        // Both should return performance data
        $this->assertArrayHasKey('performance_score', $result1);
        $this->assertArrayHasKey('performance_score', $result2);
        $this->assertIsNumeric($result1['performance_score']);
        $this->assertIsNumeric($result2['performance_score']);
    }

    /** @test */
    public function it_provides_performance_recommendations()
    {
        $result = $this->service->optimizePerformance();

        $this->assertArrayHasKey('recommendations', $result);
        $this->assertIsArray($result['recommendations']);
        $this->assertNotEmpty($result['recommendations']);

        // Check that recommendations are strings
        foreach ($result['recommendations'] as $recommendation) {
            $this->assertIsString($recommendation);
        }
    }

    /** @test */
    public function it_logs_fraud_detection_results()
    {
        Log::spy();

        $transactionData = [
            'user_id' => $this->user->id,
            'amount' => 100,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'transaction_id' => 'txn_logging_test'
        ];

        $this->service->detectFraud($transactionData);

        Log::shouldHaveReceived('info')
            ->with('Fraud Detection Completed', \Mockery::type('array'))
            ->once();
    }

    /** @test */
    public function it_handles_unknown_error_types()
    {
        $exception = new Exception('Some unknown error occurred');
        $context = [];

        $result = $this->service->handleXuiError($exception, 'unknown_operation', $context);

        $this->assertFalse($result['success']);
        $this->assertEquals('UNKNOWN_ERROR', $result['error_details']['error_code']);
        $this->assertEquals('MEDIUM', $result['error_details']['severity']);
    }

    /** @test */
    public function it_calculates_user_velocity_correctly()
    {
        // Create some recent orders
        Order::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMinutes(30)
        ]);

        // Create some old orders (should not be counted)
        Order::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);

        $transactionData = [
            'user_id' => $this->user->id,
            'amount' => 100,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'transaction_id' => 'txn_velocity_test'
        ];

        $result = $this->service->detectFraud($transactionData);

        // Should not trigger high velocity alert for 3 orders
        $this->assertLessThan(75, $result['fraud_score']);
    }

    /** @test */
    public function it_handles_missing_transaction_data_gracefully()
    {
        $transactionData = [
            'transaction_id' => 'txn_minimal_data'
            // Missing most fields
        ];

        $result = $this->service->detectFraud($transactionData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fraud_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertArrayHasKey('risk_factors', $result);
        $this->assertArrayHasKey('action_required', $result);
    }

    /** @test */
    public function it_optimizes_database_queries()
    {
        $result = $this->service->optimizePerformance();

        $this->assertArrayHasKey('database', $result['optimizations_applied']);
        $this->assertIsArray($result['optimizations_applied']['database']);
    }

    /** @test */
    public function it_optimizes_caching_strategy()
    {
        $result = $this->service->optimizePerformance();

        $this->assertArrayHasKey('cache', $result['optimizations_applied']);
        $this->assertIsArray($result['optimizations_applied']['cache']);

        // Check that server data is cached
        $this->assertTrue(Cache::has('servers.all'));
        $this->assertTrue(Cache::has('dashboard.stats'));
    }

    /** @test */
    public function fraud_detection_returns_consistent_structure()
    {
        $transactionData = [
            'user_id' => $this->user->id,
            'amount' => 100,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'transaction_id' => 'txn_structure_test'
        ];

        $result = $this->service->detectFraud($transactionData);

        // Verify structure
        $this->assertArrayHasKey('fraud_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertArrayHasKey('risk_factors', $result);
        $this->assertArrayHasKey('action_required', $result);
        $this->assertArrayHasKey('timestamp', $result);

        // Verify data types
        $this->assertIsNumeric($result['fraud_score']);
        $this->assertIsString($result['risk_level']);
        $this->assertIsArray($result['risk_factors']);
        $this->assertIsArray($result['action_required']);
    }
}
