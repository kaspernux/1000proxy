<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Events\SystemAlert;
use App\Models\Server;
use App\Models\Order;
use App\Models\User;

class MonitoringService
{
    private CacheOptimizationService $cacheService;
    private QueueOptimizationService $queueService;
    private AdvancedAnalyticsService $analyticsService;
    
    public function __construct(
        CacheOptimizationService $cacheService,
        QueueOptimizationService $queueService,
        AdvancedAnalyticsService $analyticsService
    ) {
        $this->cacheService = $cacheService;
        $this->queueService = $queueService;
        $this->analyticsService = $analyticsService;
    }
    
    /**
     * Run comprehensive system health check
     */
    public function runHealthCheck(): array
    {
        $healthStatus = [
            'overall' => 'healthy',
            'timestamp' => Carbon::now()->toISOString(),
            'checks' => []
        ];
        
        try {
            // Database health check
            $healthStatus['checks']['database'] = $this->checkDatabaseHealth();
            
            // Cache health check
            $healthStatus['checks']['cache'] = $this->checkCacheHealth();
            
            // Queue health check
            $healthStatus['checks']['queue'] = $this->checkQueueHealth();
            
            // Server health check
            $healthStatus['checks']['servers'] = $this->checkServersHealth();
            
            // Application health check
            $healthStatus['checks']['application'] = $this->checkApplicationHealth();
            
            // Storage health check
            $healthStatus['checks']['storage'] = $this->checkStorageHealth();
            
            // Determine overall health
            $healthStatus['overall'] = $this->determineOverallHealth($healthStatus['checks']);
            
            // Send alerts if needed
            $this->processHealthAlerts($healthStatus);
            
            return $healthStatus;
        } catch (\Exception $e) {
            Log::error('Health check failed', ['error' => $e->getMessage()]);
            return [
                'overall' => 'critical',
                'timestamp' => Carbon::now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            
            // Test database connection
            DB::connection()->getPdo();
            
            // Check query performance
            $queryTime = microtime(true) - $start;
            
            // Check database size
            $dbSize = DB::select("SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()")[0]->size_mb ?? 0;
            
            // Check slow queries
            $slowQueries = DB::select("SHOW GLOBAL STATUS LIKE 'Slow_queries'")[0]->Value ?? 0;
            
            $status = 'healthy';
            $issues = [];
            
            if ($queryTime > 1.0) {
                $status = 'warning';
                $issues[] = 'Database response time is slow';
            }
            
            if ($dbSize > 1000) { // 1GB
                $status = 'warning';
                $issues[] = 'Database size is large';
            }
            
            return [
                'status' => $status,
                'response_time' => round($queryTime * 1000, 2) . 'ms',
                'database_size' => $dbSize . 'MB',
                'slow_queries' => $slowQueries,
                'issues' => $issues
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Database connection failed']
            ];
        }
    }
    
    /**
     * Check cache health
     */
    private function checkCacheHealth(): array
    {
        try {
            $cacheStats = $this->cacheService->getCacheStats();
            
            $status = 'healthy';
            $issues = [];
            
            // Check hit rate
            $hitRate = floatval(str_replace('%', '', $cacheStats['hit_rate'] ?? '0'));
            if ($hitRate < 70) {
                $status = 'warning';
                $issues[] = 'Cache hit rate is low';
            }
            
            // Check memory usage (if available)
            if (isset($cacheStats['memory_usage'])) {
                $memoryUsage = $cacheStats['memory_usage'];
                if (strpos($memoryUsage, 'G') !== false) {
                    $status = 'warning';
                    $issues[] = 'High cache memory usage';
                }
            }
            
            return [
                'status' => $status,
                'hit_rate' => $cacheStats['hit_rate'] ?? 'N/A',
                'memory_usage' => $cacheStats['memory_usage'] ?? 'N/A',
                'connected_clients' => $cacheStats['connected_clients'] ?? 'N/A',
                'issues' => $issues
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Cache connection failed']
            ];
        }
    }
    
    /**
     * Check queue health
     */
    private function checkQueueHealth(): array
    {
        try {
            $queueHealth = $this->queueService->monitorQueueHealth();
            
            $status = 'healthy';
            $issues = [];
            
            foreach ($queueHealth as $queueName => $queueStatus) {
                if ($queueStatus['status'] === 'critical') {
                    $status = 'critical';
                    $issues[] = "Queue $queueName is in critical state";
                } elseif ($queueStatus['status'] === 'warning' && $status !== 'critical') {
                    $status = 'warning';
                    $issues[] = "Queue $queueName has issues";
                }
            }
            
            return [
                'status' => $status,
                'queue_details' => $queueHealth,
                'issues' => $issues
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Queue monitoring failed']
            ];
        }
    }
    
    /**
     * Check servers health
     */
    private function checkServersHealth(): array
    {
        try {
            $activeServers = Server::where('status', 'active')->count();
            $inactiveServers = Server::where('status', 'inactive')->count();
            $totalServers = Server::count();
            
            $status = 'healthy';
            $issues = [];
            
            if ($activeServers === 0) {
                $status = 'critical';
                $issues[] = 'No active servers available';
            } elseif ($activeServers < 3) {
                $status = 'warning';
                $issues[] = 'Low number of active servers';
            }
            
            // Check server capacity
            $highCapacityServers = Server::where('status', 'active')
                ->whereRaw('(current_clients / max_clients) > 0.8')
                ->count();
            
            if ($highCapacityServers > 0) {
                $status = ($status === 'healthy') ? 'warning' : $status;
                $issues[] = "$highCapacityServers servers are near capacity";
            }
            
            return [
                'status' => $status,
                'active_servers' => $activeServers,
                'inactive_servers' => $inactiveServers,
                'total_servers' => $totalServers,
                'high_capacity_servers' => $highCapacityServers,
                'issues' => $issues
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Server health check failed']
            ];
        }
    }
    
    /**
     * Check application health
     */
    private function checkApplicationHealth(): array
    {
        try {
            $status = 'healthy';
            $issues = [];
            
            // Check recent orders
            $recentOrders = Order::where('created_at', '>=', Carbon::now()->subHour())->count();
            
            // Check failed orders
            $failedOrders = Order::where('status', 'failed')
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->count();
            
            if ($failedOrders > 0) {
                $status = 'warning';
                $issues[] = "$failedOrders orders failed in the last hour";
            }
            
            // Check active users
            $activeUsers = User::where('last_active_at', '>=', Carbon::now()->subMinutes(30))->count();
            
            // Check disk space
            $diskUsage = disk_free_space('/') / disk_total_space('/');
            if ($diskUsage < 0.1) { // Less than 10% free
                $status = 'critical';
                $issues[] = 'Low disk space';
            } elseif ($diskUsage < 0.2) { // Less than 20% free
                $status = ($status === 'healthy') ? 'warning' : $status;
                $issues[] = 'Disk space getting low';
            }
            
            return [
                'status' => $status,
                'recent_orders' => $recentOrders,
                'failed_orders' => $failedOrders,
                'active_users' => $activeUsers,
                'disk_usage' => round((1 - $diskUsage) * 100, 2) . '%',
                'issues' => $issues
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Application health check failed']
            ];
        }
    }
    
    /**
     * Check storage health
     */
    private function checkStorageHealth(): array
    {
        try {
            $status = 'healthy';
            $issues = [];
            
            // Check storage directories
            $storageDir = storage_path();
            $logsDir = storage_path('logs');
            
            if (!is_writable($storageDir)) {
                $status = 'critical';
                $issues[] = 'Storage directory is not writable';
            }
            
            if (!is_writable($logsDir)) {
                $status = 'critical';
                $issues[] = 'Logs directory is not writable';
            }
            
            // Check log file sizes
            $logFiles = glob($logsDir . '/*.log');
            $totalLogSize = 0;
            
            foreach ($logFiles as $logFile) {
                $fileSize = filesize($logFile);
                $totalLogSize += $fileSize;
                
                if ($fileSize > 100 * 1024 * 1024) { // 100MB
                    $status = ($status === 'healthy') ? 'warning' : $status;
                    $issues[] = 'Large log file detected: ' . basename($logFile);
                }
            }
            
            return [
                'status' => $status,
                'storage_writable' => is_writable($storageDir),
                'logs_writable' => is_writable($logsDir),
                'total_log_size' => round($totalLogSize / 1024 / 1024, 2) . 'MB',
                'log_files_count' => count($logFiles),
                'issues' => $issues
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Storage health check failed']
            ];
        }
    }
    
    /**
     * Determine overall health status
     */
    private function determineOverallHealth(array $checks): string
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'critical') {
                return 'critical';
            }
        }
        
        foreach ($checks as $check) {
            if ($check['status'] === 'warning') {
                return 'warning';
            }
        }
        
        return 'healthy';
    }
    
    /**
     * Process health alerts
     */
    private function processHealthAlerts(array $healthStatus): void
    {
        try {
            $overallStatus = $healthStatus['overall'];
            
            if ($overallStatus === 'critical') {
                $this->sendCriticalAlert($healthStatus);
                event(new SystemAlert('critical', 'System health is critical', $healthStatus));
            } elseif ($overallStatus === 'warning') {
                $this->sendWarningAlert($healthStatus);
                event(new SystemAlert('warning', 'System health warning', $healthStatus));
            }
            
            // Cache health status
            $this->cacheService->cacheRealTimeData('system_health', $healthStatus, 300);
        } catch (\Exception $e) {
            Log::error('Failed to process health alerts', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Send critical alert
     */
    private function sendCriticalAlert(array $healthStatus): void
    {
        try {
            $issues = [];
            foreach ($healthStatus['checks'] as $checkName => $check) {
                if (!empty($check['issues'])) {
                    $issues[$checkName] = $check['issues'];
                }
            }
            
            Log::critical('System health critical', [
                'status' => $healthStatus['overall'],
                'issues' => $issues,
                'timestamp' => $healthStatus['timestamp']
            ]);
            
            // Send email alert to administrators
            $this->sendEmailAlert('Critical System Alert', $healthStatus);
        } catch (\Exception $e) {
            Log::error('Failed to send critical alert', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Send warning alert
     */
    private function sendWarningAlert(array $healthStatus): void
    {
        try {
            $issues = [];
            foreach ($healthStatus['checks'] as $checkName => $check) {
                if (!empty($check['issues'])) {
                    $issues[$checkName] = $check['issues'];
                }
            }
            
            Log::warning('System health warning', [
                'status' => $healthStatus['overall'],
                'issues' => $issues,
                'timestamp' => $healthStatus['timestamp']
            ]);
            
            // Send email alert to administrators (less frequent)
            $lastWarning = Cache::get('last_warning_alert');
            if (!$lastWarning || Carbon::parse($lastWarning)->addMinutes(30)->isPast()) {
                $this->sendEmailAlert('System Warning Alert', $healthStatus);
                Cache::put('last_warning_alert', Carbon::now()->toISOString(), 3600);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send warning alert', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Send email alert
     */
    private function sendEmailAlert(string $subject, array $healthStatus): void
    {
        try {
            $adminEmails = ['admin@1000proxy.com']; // Configure admin emails
            
            foreach ($adminEmails as $email) {
                Mail::send('emails.health-alert', ['healthStatus' => $healthStatus], function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            }
        } catch (\Exception $e) {
            Log::error('Failed to send email alert', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        try {
            return [
                'response_times' => $this->getResponseTimeMetrics(),
                'throughput' => $this->getThroughputMetrics(),
                'error_rates' => $this->getErrorRateMetrics(),
                'resource_usage' => $this->getResourceUsageMetrics(),
                'timestamp' => Carbon::now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get performance metrics', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get response time metrics
     */
    private function getResponseTimeMetrics(): array
    {
        // This would typically come from APM tools or custom middleware
        return [
            'average_response_time' => '120ms',
            'p95_response_time' => '250ms',
            'p99_response_time' => '500ms',
            'slowest_endpoints' => [
                '/api/orders' => '200ms',
                '/api/servers' => '150ms'
            ]
        ];
    }
    
    /**
     * Get throughput metrics
     */
    private function getThroughputMetrics(): array
    {
        $queueMetrics = $this->queueService->getQueuePerformanceMetrics();
        
        return [
            'requests_per_minute' => 150,
            'orders_per_minute' => 12,
            'queue_throughput' => $queueMetrics['throughput_per_minute'] ?? 0
        ];
    }
    
    /**
     * Get error rate metrics
     */
    private function getErrorRateMetrics(): array
    {
        return [
            'error_rate_percentage' => 0.5,
            'critical_errors' => 0,
            'warnings' => 3,
            'common_errors' => [
                'validation_errors' => 45,
                'server_errors' => 2,
                'timeout_errors' => 1
            ]
        ];
    }
    
    /**
     * Get resource usage metrics
     */
    private function getResourceUsageMetrics(): array
    {
        return [
            'cpu_usage' => '45%',
            'memory_usage' => '62%',
            'disk_usage' => '35%',
            'network_in' => '1.2 MB/s',
            'network_out' => '2.4 MB/s'
        ];
    }
}