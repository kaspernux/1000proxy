<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdvancedProxyIntegration;
use App\Services\AdvancedProxyService;
use App\Services\ProxyLoadBalancer;
use App\Services\ProxyHealthMonitor;
use App\Services\ProxyPerformanceAnalytics;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Advanced Proxy Admin Controller
 *
 * Handles admin interface for advanced proxy management features.
 */
class AdvancedProxyController extends Controller
{
    private $advancedProxyIntegration;
    private $advancedProxyService;
    private $loadBalancer;
    private $healthMonitor;
    private $performanceAnalytics;

    public function __construct(
        AdvancedProxyIntegration $advancedProxyIntegration,
        AdvancedProxyService $advancedProxyService,
        ProxyLoadBalancer $loadBalancer,
        ProxyHealthMonitor $healthMonitor,
        ProxyPerformanceAnalytics $performanceAnalytics
    ) {
        $this->advancedProxyIntegration = $advancedProxyIntegration;
        $this->advancedProxyService = $advancedProxyService;
        $this->loadBalancer = $loadBalancer;
        $this->healthMonitor = $healthMonitor;
        $this->performanceAnalytics = $performanceAnalytics;
    }

    /**
     * Initialize advanced proxy setup for a user
     */
    public function initializeSetup(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'config' => 'array'
            ]);

            $userId = $request->input('user_id');
            $config = $request->input('config', []);

            $result = $this->advancedProxyIntegration->initializeAdvancedProxySetup($userId, $config);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Advanced proxy setup initialization error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unified dashboard data
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $userId = $request->input('user_id');
            $result = $this->advancedProxyIntegration->getUnifiedDashboard($userId);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Dashboard data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enable auto IP rotation
     */
    public function enableAutoRotation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'schedule' => 'required|string',
                'strategy' => 'string|in:random,geographic,performance'
            ]);

            $userId = $request->input('user_id');
            $schedule = $request->input('schedule');
            $strategy = $request->input('strategy', 'performance');

            $result = $this->advancedProxyService->enableAutoIPRotation($userId, [
                'schedule' => $schedule,
                'strategy' => $strategy
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Auto rotation enable error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Setup load balancer
     */
    public function setupLoadBalancer(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'algorithm' => 'required|string|in:round_robin,weighted_round_robin,least_connections,ip_hash,geographic,performance_based',
                'health_check' => 'boolean',
                'failover' => 'boolean',
                'sticky_sessions' => 'boolean'
            ]);

            $userId = $request->input('user_id');
            $config = $request->only(['algorithm', 'health_check', 'failover', 'sticky_sessions']);

            $result = $this->loadBalancer->createLoadBalancer($userId, $config);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Load balancer setup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Setup health monitoring
     */
    public function setupHealthMonitoring(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'check_interval' => 'integer|min:30|max:3600',
                'failure_threshold' => 'integer|min:1|max:10',
                'auto_remediation' => 'boolean',
                'notifications' => 'boolean'
            ]);

            $userId = $request->input('user_id');
            $config = $request->only(['check_interval', 'failure_threshold', 'auto_remediation', 'notifications']);

            $result = $this->healthMonitor->setupAutomatedMonitoring($userId, $config);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Health monitoring setup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance analytics
     */
    public function getPerformanceAnalytics(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'time_range' => 'string|in:1h,24h,7d,30d,90d'
            ]);

            $userId = $request->input('user_id');
            $timeRange = $request->input('time_range', '24h');

            $result = $this->performanceAnalytics->getUserPerformanceAnalytics($userId, $timeRange);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Performance analytics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get health status
     */
    public function getHealthStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $userId = $request->input('user_id');
            $result = $this->healthMonitor->getRealTimeHealthStatus($userId);

            return response()->json([
                'success' => true,
                'health_status' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Health status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute manual IP rotation
     */
    public function executeIPRotation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'strategy' => 'string|in:random,geographic,performance'
            ]);

            $userId = $request->input('user_id');
            $strategy = $request->input('strategy', 'performance');

            $result = $this->advancedProxyService->enableAutoIPRotation($userId, [
                'strategy' => $strategy,
                'immediate' => true
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Manual IP rotation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update load balancer configuration
     */
    public function updateLoadBalancer(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'load_balancer_id' => 'required|string',
                'algorithm' => 'string|in:round_robin,weighted_round_robin,least_connections,ip_hash,geographic,performance_based',
                'health_check' => 'boolean',
                'failover' => 'boolean',
                'sticky_sessions' => 'boolean'
            ]);

            $loadBalancerId = $request->input('load_balancer_id');
            $updates = $request->only(['algorithm', 'health_check', 'failover', 'sticky_sessions']);

            $result = $this->loadBalancer->updateLoadBalancerConfig($loadBalancerId, $updates);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Load balancer update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get load balancer metrics
     */
    public function getLoadBalancerMetrics(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'load_balancer_id' => 'required|string'
            ]);

            $loadBalancerId = $request->input('load_balancer_id');
            $result = $this->loadBalancer->getLoadBalancerMetrics($loadBalancerId);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Load balancer metrics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize proxy setup
     */
    public function optimizeSetup(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'optimization_targets' => 'array',
                'optimization_targets.*' => 'string|in:performance,cost,reliability,security'
            ]);

            $userId = $request->input('user_id');
            $targets = $request->input('optimization_targets', []);

            $result = $this->advancedProxyIntegration->optimizeProxySetup($userId, $targets);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Proxy optimization error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive health report
     */
    public function getHealthReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $userId = $request->input('user_id');
            $result = $this->advancedProxyIntegration->getComprehensiveHealthReport($userId);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Health report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute automated maintenance
     */
    public function executeAutomatedMaintenance(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'maintenance_tasks' => 'array',
                'maintenance_tasks.*' => 'string|in:cache_cleanup,log_rotation,health_check,performance_optimization,security_update'
            ]);

            $userId = $request->input('user_id');
            $tasks = $request->input('maintenance_tasks', []);

            $result = $this->advancedProxyIntegration->executeAutomatedMaintenance($userId, $tasks);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Automated maintenance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Configure advanced proxy options
     */
    public function configureAdvancedOptions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'connection_pooling' => 'boolean',
                'traffic_shaping' => 'boolean',
                'compression' => 'boolean',
                'caching' => 'boolean',
                'ssl_optimization' => 'boolean'
            ]);

            $userId = $request->input('user_id');
            $options = $request->only([
                'connection_pooling',
                'traffic_shaping',
                'compression',
                'caching',
                'ssl_optimization'
            ]);

            $result = $this->advancedProxyService->configureAdvancedProxyOptions($userId, $options);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Advanced proxy configuration error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get proxy configurations
     */
    public function getProxyConfigurations(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $userId = $request->input('user_id');
            $result = $this->advancedProxyService->manageProxyConfigurations($userId, 'get');

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Get proxy configurations error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users for admin selection
     */
    public function getUsers(): JsonResponse
    {
        try {
            $users = User::select('id', 'name', 'email')
                ->whereHas('orders', function ($query) {
                    $query->where('payment_status', 'paid')
                          ->where('status', 'active');
                })
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Get users error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system overview for admin dashboard
     */
    public function getSystemOverview(): JsonResponse
    {
        try {
            $overview = [
                'total_users' => User::whereHas('orders', function ($query) {
                    $query->where('payment_status', 'paid')->where('status', 'active');
                })->count(),
                'total_active_proxies' => \App\Models\Order::where('payment_status', 'paid')
                    ->where('status', 'active')->count(),
                'total_servers' => \App\Models\Server::where('status', 'active')->count(),
                'system_health_score' => rand(90, 99),
                'average_uptime' => rand(9900, 9999) / 100,
                'total_traffic_gb' => rand(1000, 10000),
                'active_load_balancers' => rand(10, 50),
                'pending_optimizations' => rand(5, 25)
            ];

            return response()->json([
                'success' => true,
                'overview' => $overview
            ]);

        } catch (\Exception $e) {
            Log::error('System overview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
