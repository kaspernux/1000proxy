<?php

namespace App\Services;

use App\Models\User;
use App\Models\Server;
use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Proxy Health Monitor Service
 *
 * Monitors proxy health, performance, and availability with automated remediation.
 */
class ProxyHealthMonitor
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Execute comprehensive health check for all monitored proxies
     */
    public function executeHealthCheck(): array
    {
        try {
            $monitoredProxies = $this->getMonitoredProxies();
            $healthResults = [];
            $healthSummary = [
                'total_checked' => 0,
                'healthy' => 0,
                'unhealthy' => 0,
                'warning' => 0,
                'critical' => 0
            ];

            foreach ($monitoredProxies as $proxy) {
                $healthResult = $this->checkProxyHealth($proxy);
                $healthResults[] = $healthResult;

                // Update summary
                $healthSummary['total_checked']++;
                $healthSummary[$healthResult['status']]++;

                // Store health result
                $this->storeHealthResult($proxy, $healthResult);

                // Trigger alerts if needed
                if ($healthResult['status'] === 'unhealthy' || $healthResult['status'] === 'critical') {
                    $this->handleUnhealthyProxy($proxy, $healthResult);
                }

                // Apply auto-remediation if enabled
                if ($this->isAutoRemediationEnabled($proxy)) {
                    $this->applyAutoRemediation($proxy, $healthResult);
                }
            }

            // Generate health report
            $healthReport = $this->generateHealthReport($healthResults, $healthSummary);

            Log::info('Proxy health check completed', $healthSummary);

            return [
                'success' => true,
                'summary' => $healthSummary,
                'results' => $healthResults,
                'report' => $healthReport,
                'check_time' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Proxy health check error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check health status for a specific proxy
     */
    public function checkProxyHealth($proxy): array
    {
        try {
            $healthMetrics = [
                'response_time' => $this->checkResponseTime($proxy),
                'connectivity' => $this->checkConnectivity($proxy),
                'bandwidth' => $this->checkBandwidthUsage($proxy),
                'error_rate' => $this->checkErrorRate($proxy),
                'resource_usage' => $this->checkResourceUsage($proxy),
                'ssl_status' => $this->checkSSLStatus($proxy),
                'geo_location' => $this->checkGeoLocation($proxy)
            ];

            // Calculate overall health score
            $healthScore = $this->calculateHealthScore($healthMetrics);
            $healthStatus = $this->determineHealthStatus($healthScore, $healthMetrics);

            $healthResult = [
                'proxy_id' => $proxy['order']->id,
                'server_id' => $proxy['server']->id,
                'user_id' => $proxy['order']->user_id,
                'health_score' => $healthScore,
                'status' => $healthStatus,
                'metrics' => $healthMetrics,
                'check_time' => now()->toISOString(),
                'issues' => $this->identifyIssues($healthMetrics),
                'recommendations' => $this->generateRecommendations($healthMetrics)
            ];

            return $healthResult;
        } catch (\Exception $e) {
            Log::error("Proxy health check error for proxy {$proxy['order']->id}: " . $e->getMessage());
            return [
                'proxy_id' => $proxy['order']->id,
                'status' => 'error',
                'error' => $e->getMessage(),
                'check_time' => now()->toISOString()
            ];
        }
    }

    /**
     * Get real-time health status for a user's proxies
     */
    public function getRealTimeHealthStatus($userId): array
    {
        try {
            $userProxies = $this->getUserProxies($userId);
            $healthStatuses = [];

            foreach ($userProxies as $proxy) {
                $cachedHealth = Cache::get("health_status_{$proxy['order']->id}");

                if ($cachedHealth) {
                    $healthStatuses[] = $cachedHealth;
                } else {
                    // Perform quick health check
                    $quickHealth = $this->performQuickHealthCheck($proxy);
                    $healthStatuses[] = $quickHealth;

                    // Cache result for 5 minutes
                    Cache::put("health_status_{$proxy['order']->id}", $quickHealth, 300);
                }
            }

            $summary = $this->summarizeHealthStatuses($healthStatuses);

            return [
                'success' => true,
                'user_id' => $userId,
                'summary' => $summary,
                'proxy_statuses' => $healthStatuses,
                'last_updated' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("Real-time health status error for user {$userId}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Setup automated health monitoring for a user
     */
    public function setupAutomatedMonitoring($userId, $config): array
    {
        try {
            $monitoringConfig = [
                'user_id' => $userId,
                'enabled' => true,
                'check_interval' => $config['check_interval'] ?? 60, // seconds
                'alert_thresholds' => [
                    'response_time' => $config['response_threshold'] ?? 2000, // ms
                    'error_rate' => $config['error_threshold'] ?? 5, // percentage
                    'bandwidth_usage' => $config['bandwidth_threshold'] ?? 80, // percentage
                    'uptime' => $config['uptime_threshold'] ?? 95 // percentage
                ],
                'notification_settings' => [
                    'email_alerts' => $config['email_alerts'] ?? true,
                    'webhook_url' => $config['webhook_url'] ?? null,
                    'slack_webhook' => $config['slack_webhook'] ?? null,
                    'telegram_chat_id' => $config['telegram_chat_id'] ?? null
                ],
                'auto_remediation' => [
                    'enabled' => $config['auto_remediation'] ?? true,
                    'restart_on_failure' => $config['restart_on_failure'] ?? true,
                    'rotate_on_poor_performance' => $config['rotate_on_poor_performance'] ?? true,
                    'blacklist_failed_ips' => $config['blacklist_failed_ips'] ?? true,
                    'max_restart_attempts' => $config['max_restart_attempts'] ?? 3
                ],
                'reporting' => [
                    'daily_reports' => $config['daily_reports'] ?? true,
                    'weekly_summaries' => $config['weekly_summaries'] ?? true,
                    'performance_analytics' => $config['performance_analytics'] ?? true
                ],
                'created_at' => now()->toISOString()
            ];

            // Store monitoring configuration
            Cache::put("health_monitoring_{$userId}", $monitoringConfig, 86400);

            // Initialize health tracking
            $this->initializeHealthTracking($userId);

            Log::info("Automated health monitoring setup for user {$userId}");

            return [
                'success' => true,
                'message' => 'Automated health monitoring configured successfully',
                'config' => $monitoringConfig,
                'monitoring_id' => "health_monitor_{$userId}"
            ];
        } catch (\Exception $e) {
            Log::error("Setup automated monitoring error for user {$userId}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate comprehensive health analytics
     */
    public function generateHealthAnalytics($userId, $timeRange = '24h'): array
    {
        try {
            $period = $this->parseTimeRange($timeRange);
            $healthData = $this->getHealthDataForPeriod($userId, $period);

            $analytics = [
                'availability_metrics' => $this->calculateAvailabilityMetrics($healthData),
                'performance_trends' => $this->analyzePerformanceTrends($healthData),
                'error_analysis' => $this->analyzeErrors($healthData),
                'resource_utilization' => $this->analyzeResourceUtilization($healthData),
                'geographic_performance' => $this->analyzeGeographicPerformance($healthData),
                'comparative_analysis' => $this->performComparativeAnalysis($healthData),
                'predictions' => $this->generateHealthPredictions($healthData),
                'recommendations' => $this->generateAnalyticsRecommendations($healthData)
            ];

            return [
                'success' => true,
                'user_id' => $userId,
                'time_range' => $timeRange,
                'analytics' => $analytics,
                'generated_at' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("Health analytics generation error for user {$userId}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Private helper methods

    private function getMonitoredProxies(): Collection
    {
        return collect(User::whereHas('orders', function ($query) {
            $query->where('payment_status', 'paid')
                  ->where('status', 'up');
        })->with(['orders.serverPlan.server'])
          ->get()
          ->flatMap(function ($user) {
              return $user->orders->where('payment_status', 'paid')
                                 ->where('status', 'up')
                                 ->map(function ($order) {
                                     return [
                                         'order' => $order,
                                         'server' => $order->serverPlan->server,
                                         'plan' => $order->serverPlan,
                                         'user' => $order->user
                                     ];
                                 });
          }));
    }

    private function getUserProxies($userId): Collection
    {
        $user = User::find($userId);
        if (!$user) return collect();

        return collect($user->orders()
            ->where('payment_status', 'paid')
            ->where('status', 'up')
            ->with(['serverPlan.server'])
            ->get()
            ->map(function ($order) {
                return [
                    'order' => $order,
                    'server' => $order->serverPlan->server,
                    'plan' => $order->serverPlan
                ];
            }));
    }

    private function checkResponseTime($proxy): array
    {
        // Simulate response time check
        $responseTime = rand(50, 2000);
        return [
            'value' => $responseTime,
            'status' => $responseTime < 1000 ? 'good' : ($responseTime < 2000 ? 'warning' : 'critical'),
            'timestamp' => now()->toISOString()
        ];
    }

    private function checkConnectivity($proxy): array
    {
        // Simulate connectivity check
        $isConnected = rand(1, 100) <= 95; // 95% success rate
        return [
            'connected' => $isConnected,
            'status' => $isConnected ? 'good' : 'critical',
            'timestamp' => now()->toISOString()
        ];
    }

    private function checkBandwidthUsage($proxy): array
    {
        $usage = rand(10, 95);
        return [
            'usage_percentage' => $usage,
            'status' => $usage < 70 ? 'good' : ($usage < 85 ? 'warning' : 'critical'),
            'timestamp' => now()->toISOString()
        ];
    }

    private function checkErrorRate($proxy): array
    {
        $errorRate = rand(0, 15);
        return [
            'error_rate' => $errorRate,
            'status' => $errorRate < 3 ? 'good' : ($errorRate < 8 ? 'warning' : 'critical'),
            'timestamp' => now()->toISOString()
        ];
    }

    private function checkResourceUsage($proxy): array
    {
        return [
            'cpu_usage' => rand(10, 85),
            'memory_usage' => rand(20, 80),
            'disk_usage' => rand(15, 70),
            'status' => 'good',
            'timestamp' => now()->toISOString()
        ];
    }

    private function checkSSLStatus($proxy): array
    {
        return [
            'ssl_valid' => rand(1, 100) <= 98,
            'expires_in_days' => rand(30, 365),
            'status' => 'good',
            'timestamp' => now()->toISOString()
        ];
    }

    private function checkGeoLocation($proxy): array
    {
        return [
            'location_accurate' => rand(1, 100) <= 95,
            'expected_country' => 'US',
            'actual_country' => 'US',
            'status' => 'good',
            'timestamp' => now()->toISOString()
        ];
    }

    private function calculateHealthScore($metrics): int
    {
        $scores = [];

        foreach ($metrics as $metric) {
            switch ($metric['status']) {
                case 'good':
                    $scores[] = 100;
                    break;
                case 'warning':
                    $scores[] = 70;
                    break;
                case 'critical':
                    $scores[] = 30;
                    break;
                default:
                    $scores[] = 50;
            }
        }

        return count($scores) > 0 ? (int) array_sum($scores) / count($scores) : 0;
    }

    private function determineHealthStatus($score, $metrics): string
    {
        if ($score >= 90) return 'healthy';
        if ($score >= 70) return 'warning';
        if ($score >= 50) return 'unhealthy';
        return 'critical';
    }

    private function identifyIssues($metrics): array
    {
        $issues = [];

        foreach ($metrics as $metricName => $metric) {
            if (isset($metric['status']) && in_array($metric['status'], ['warning', 'critical'])) {
                $issues[] = [
                    'metric' => $metricName,
                    'status' => $metric['status'],
                    'value' => $metric['value'] ?? $metric,
                    'description' => $this->getIssueDescription($metricName, $metric)
                ];
            }
        }

        return $issues;
    }

    private function generateRecommendations($metrics): array
    {
        $recommendations = [];

        if (isset($metrics['response_time']['status']) && $metrics['response_time']['status'] !== 'good') {
            $recommendations[] = 'Consider optimizing server configuration or switching to a higher performance plan';
        }

        if (isset($metrics['bandwidth']['status']) && $metrics['bandwidth']['status'] !== 'good') {
            $recommendations[] = 'Monitor bandwidth usage and consider upgrading bandwidth allocation';
        }

        if (isset($metrics['error_rate']['status']) && $metrics['error_rate']['status'] !== 'good') {
            $recommendations[] = 'Investigate error patterns and consider IP rotation or server maintenance';
        }

        return $recommendations;
    }

    private function performQuickHealthCheck($proxy): array
    {
        $responseTime = rand(50, 1500);
        $isHealthy = $responseTime < 1000 && rand(1, 100) <= 95;

        return [
            'proxy_id' => $proxy['order']->id,
            'status' => $isHealthy ? 'healthy' : 'warning',
            'response_time' => $responseTime,
            'last_check' => now()->toISOString()
        ];
    }

    private function summarizeHealthStatuses($statuses): array
    {
        $summary = ['healthy' => 0, 'warning' => 0, 'unhealthy' => 0, 'critical' => 0];

        foreach ($statuses as $status) {
            $summary[$status['status']] = ($summary[$status['status']] ?? 0) + 1;
        }

        return $summary;
    }

    private function storeHealthResult($proxy, $result): void
    {
        $key = "health_result_{$proxy['order']->id}";
        Cache::put($key, $result, 3600);

        // Store in health history
        $historyKey = "health_history_{$proxy['order']->id}";
        $history = Cache::get($historyKey, []);
        $history[] = $result;

        // Keep only last 100 entries
        if (count($history) > 100) {
            $history = array_slice($history, -100);
        }

        Cache::put($historyKey, $history, 86400);
    }

    private function handleUnhealthyProxy($proxy, $healthResult): void
    {
        $userId = $proxy['order']->user_id;
        $monitoringConfig = Cache::get("health_monitoring_{$userId}");

        if ($monitoringConfig && ($monitoringConfig['notification_settings']['email_alerts'] ?? false)) {
            $this->sendHealthAlert($proxy, $healthResult, $monitoringConfig);
        }
    }

    private function sendHealthAlert($proxy, $healthResult, $config): void
    {
        $this->notificationService->sendEmail(
            $proxy['order']->user->email,
            'Proxy Health Alert',
            "Your proxy (ID: {$proxy['order']->id}) is experiencing health issues. Status: {$healthResult['status']}"
        );
    }

    private function applyAutoRemediation($proxy, $healthResult): void
    {
        $userId = $proxy['order']->user_id;
        $monitoringConfig = Cache::get("health_monitoring_{$userId}");

        if (!$monitoringConfig || !($monitoringConfig['auto_remediation']['enabled'] ?? false)) {
            return;
        }

        // Apply remediation based on issues
        foreach ($healthResult['issues'] ?? [] as $issue) {
            switch ($issue['metric']) {
                case 'response_time':
                    if ($monitoringConfig['auto_remediation']['restart_on_failure'] ?? false) {
                        $this->restartProxy($proxy);
                    }
                    break;
                case 'error_rate':
                    if ($monitoringConfig['auto_remediation']['rotate_on_poor_performance'] ?? false) {
                        $this->triggerIPRotation($proxy);
                    }
                    break;
            }
        }
    }

    // Mock implementations for complex operations
    private function restartProxy($proxy): void { Log::info("Restarting proxy {$proxy['order']->id}"); }
    private function triggerIPRotation($proxy): void { Log::info("Triggering IP rotation for proxy {$proxy['order']->id}"); }
    private function initializeHealthTracking($userId): void { Log::info("Initialized health tracking for user {$userId}"); }
    private function parseTimeRange($range): array { return ['start' => now()->subDay(), 'end' => now()]; }
    private function getHealthDataForPeriod($userId, $period): array { return []; }
    private function calculateAvailabilityMetrics($data): array { return ['uptime' => rand(95, 99.9)]; }
    private function analyzePerformanceTrends($data): array { return ['trend' => 'stable']; }
    private function analyzeErrors($data): array { return ['error_count' => rand(0, 10)]; }
    private function analyzeResourceUtilization($data): array { return ['avg_cpu' => rand(20, 80)]; }
    private function analyzeGeographicPerformance($data): array { return ['regions' => ['US' => 'good']]; }
    private function performComparativeAnalysis($data): array { return ['comparison' => 'above_average']; }
    private function generateHealthPredictions($data): array { return ['next_24h' => 'stable']; }
    private function generateAnalyticsRecommendations($data): array { return ['Maintain current configuration']; }
    private function generateHealthReport($results, $summary): array { return ['status' => 'Report generated']; }
    private function isAutoRemediationEnabled($proxy): bool { return true; }
    private function getIssueDescription($metric, $data): string { return "Issue detected in {$metric}"; }
}
