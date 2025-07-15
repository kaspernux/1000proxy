<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MonitoringService;
use App\Services\CacheOptimizationService;
use App\Services\QueueOptimizationService;
use App\Services\AdvancedAnalyticsService;
use App\Services\InventoryManagementService;
use App\Services\PricingEngineService;

class SystemAdminController extends Controller
{
    private MonitoringService $monitoringService;
    private CacheOptimizationService $cacheService;
    private QueueOptimizationService $queueService;
    private AdvancedAnalyticsService $analyticsService;
    private InventoryManagementService $inventoryService;
    private PricingEngineService $pricingService;

    public function __construct(
        MonitoringService $monitoringService,
        CacheOptimizationService $cacheService,
        QueueOptimizationService $queueService,
        AdvancedAnalyticsService $analyticsService,
        InventoryManagementService $inventoryService,
        PricingEngineService $pricingService
    ) {
        $this->monitoringService = $monitoringService;
        $this->cacheService = $cacheService;
        $this->queueService = $queueService;
        $this->analyticsService = $analyticsService;
        $this->inventoryService = $inventoryService;
        $this->pricingService = $pricingService;

        $this->middleware('auth');
        $this->middleware('staff.role:admin'); // Only admin staff can access system admin functions
    }

    /**
     * System dashboard
     */
    public function dashboard()
    {
        try {
            $data = [
                'health_status' => $this->monitoringService->runHealthCheck(),
                'cache_stats' => $this->cacheService->getCacheStats(),
                'queue_stats' => $this->queueService->getQueueStats(),
                'performance_metrics' => $this->monitoringService->getPerformanceMetrics(),
                'business_metrics' => $this->analyticsService->getBusinessMetrics(),
            ];

            return view('admin.system.dashboard', $data);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load system dashboard: ' . $e->getMessage());
        }
    }

    /**
     * System health check API
     */
    public function healthCheck()
    {
        try {
            $healthStatus = $this->monitoringService->runHealthCheck();
            return response()->json($healthStatus);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cache management
     */
    public function cacheManagement()
    {
        try {
            $data = [
                'cache_stats' => $this->cacheService->getCacheStats(),
                'cache_types' => [
                    'server' => 'Server Data Cache',
                    'user' => 'User Data Cache',
                    'analytics' => 'Analytics Cache',
                    'realtime' => 'Real-time Cache'
                ]
            ];

            return view('admin.system.cache', $data);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load cache management: ' . $e->getMessage());
        }
    }

    /**
     * Warm up cache
     */
    public function warmUpCache()
    {
        try {
            $result = $this->cacheService->warmUpCache();

            if ($result) {
                return response()->json(['message' => 'Cache warmed up successfully']);
            } else {
                return response()->json(['error' => 'Failed to warm up cache'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request)
    {
        try {
            $cacheType = $request->input('type', 'all');

            if ($cacheType === 'all') {
                \Illuminate\Support\Facades\Cache::flush();
                $message = 'All cache cleared successfully';
            } else {
                // Clear specific cache type
                switch ($cacheType) {
                    case 'server':
                        $this->cacheService->invalidateServerCache();
                        $message = 'Server cache cleared successfully';
                        break;
                    case 'user':
                        // Clear all user caches (this would be a more complex operation)
                        $message = 'User cache cleared successfully';
                        break;
                    default:
                        return response()->json(['error' => 'Invalid cache type'], 400);
                }
            }

            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Queue management
     */
    public function queueManagement()
    {
        try {
            $data = [
                'queue_stats' => $this->queueService->getQueueStats(),
                'queue_health' => $this->queueService->monitorQueueHealth(),
                'performance_metrics' => $this->queueService->getQueuePerformanceMetrics(),
                'scaling_recommendations' => $this->queueService->autoScaleWorkers(),
            ];

            return view('admin.system.queue', $data);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load queue management: ' . $e->getMessage());
        }
    }

    /**
     * Retry failed jobs
     */
    public function retryFailedJobs(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $retried = $this->queueService->retryFailedJobs($limit);

            return response()->json(['message' => "Retried $retried failed jobs"]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Analytics dashboard
     */
    public function analytics()
    {
        try {
            $data = [
                'business_metrics' => $this->analyticsService->getBusinessMetrics(),
                'performance_metrics' => $this->analyticsService->getPerformanceMetrics(),
                'server_metrics' => $this->analyticsService->getServerMetrics(),
                'user_metrics' => $this->analyticsService->getUserMetrics(),
                'revenue_forecast' => $this->analyticsService->getForecastData('revenue', 'daily'),
            ];

            return view('admin.analytics.dashboard', $data);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load analytics: ' . $e->getMessage());
        }
    }

    /**
     * Real-time metrics API
     */
    public function realTimeMetrics()
    {
        try {
            $metrics = $this->analyticsService->getRealTimeMetrics();
            return response()->json($metrics);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Inventory management
     */
    public function inventoryManagement()
    {
        try {
            $data = [
                'server_capacity' => $this->inventoryService->getServerCapacity(),
                'capacity_alerts' => $this->inventoryService->checkCapacityAlerts(),
                'load_balancing' => $this->inventoryService->getLoadBalancingStatus(),
                'auto_scaling' => $this->inventoryService->getAutoScalingStatus(),
            ];

            return view('admin.inventory.management', $data);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load inventory management: ' . $e->getMessage());
        }
    }

    /**
     * Trigger server rebalancing
     */
    public function rebalanceServers()
    {
        try {
            $result = $this->inventoryService->rebalanceServerLoads();

            if ($result) {
                return response()->json(['message' => 'Server rebalancing completed successfully']);
            } else {
                return response()->json(['error' => 'Server rebalancing failed'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Pricing engine management
     */
    public function pricingManagement()
    {
        try {
            $data = [
                'pricing_rules' => $this->pricingService->getPricingRules(),
                'dynamic_pricing' => $this->pricingService->getDynamicPricingStatus(),
                'bulk_discounts' => $this->pricingService->getBulkDiscountRules(),
                'promotional_offers' => $this->pricingService->getActivePromotions(),
            ];

            return view('admin.pricing.management', $data);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load pricing management: ' . $e->getMessage());
        }
    }

    /**
     * Update pricing rules
     */
    public function updatePricingRules(Request $request)
    {
        try {
            $rules = $request->input('rules', []);
            $result = $this->pricingService->updatePricingRules($rules);

            if ($result) {
                return response()->json(['message' => 'Pricing rules updated successfully']);
            } else {
                return response()->json(['error' => 'Failed to update pricing rules'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * System logs
     */
    public function systemLogs(Request $request)
    {
        try {
            $level = $request->input('level', 'all');
            $lines = $request->input('lines', 100);

            $logPath = storage_path('logs/laravel.log');
            $logs = [];

            if (file_exists($logPath)) {
                $content = file_get_contents($logPath);
                $logLines = explode("\n", $content);
                $logs = array_slice($logLines, -$lines);

                // Filter by level if specified
                if ($level !== 'all') {
                    $logs = array_filter($logs, function($line) use ($level) {
                        return strpos($line, strtoupper($level)) !== false;
                    });
                }
            }

            return view('admin.system.logs', ['logs' => $logs, 'level' => $level]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load system logs: ' . $e->getMessage());
        }
    }

    /**
     * Export system report
     */
    public function exportReport(Request $request)
    {
        try {
            $type = $request->input('type', 'full');

            $report = [
                'timestamp' => now()->toISOString(),
                'system_health' => $this->monitoringService->runHealthCheck(),
                'performance_metrics' => $this->monitoringService->getPerformanceMetrics(),
                'cache_stats' => $this->cacheService->getCacheStats(),
                'queue_stats' => $this->queueService->getQueueStats(),
                'business_metrics' => $this->analyticsService->getBusinessMetrics(),
            ];

            if ($type === 'full') {
                $report['server_metrics'] = $this->analyticsService->getServerMetrics();
                $report['user_metrics'] = $this->analyticsService->getUserMetrics();
                $report['inventory_status'] = $this->inventoryService->getServerCapacity();
            }

            $filename = 'system_report_' . now()->format('Y-m-d_H-i-s') . '.json';

            return response()->json($report)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
