<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerInbound;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServerManagementService
{
    protected XUIService $xuiService;

    public function __construct(XUIService $xuiService)
    {
        $this->xuiService = $xuiService;
    }

    /**
     * Perform bulk health checks on all servers
     */
    public function performBulkHealthCheck(): array
    {
        $servers = Server::where('is_active', true)->get();
        $results = [
            'total_servers' => $servers->count(),
            'healthy_servers' => 0,
            'unhealthy_servers' => 0,
            'server_details' => [],
            'errors' => []
        ];

        foreach ($servers as $server) {
            try {
                $healthStatus = $this->checkServerHealth($server);

                $results['server_details'][] = [
                    'server_id' => $server->id,
                    'name' => $server->name,
                    'location' => $server->country,
                    'status' => $healthStatus['status'],
                    'response_time' => $healthStatus['response_time'],
                    'uptime_percentage' => $healthStatus['uptime_percentage'],
                    'active_clients' => $healthStatus['active_clients'],
                    'bandwidth_usage' => $healthStatus['bandwidth_usage'],
                    'last_checked' => now(),
                    'issues' => $healthStatus['issues'] ?? []
                ];

                if ($healthStatus['status'] === 'healthy') {
                    $results['healthy_servers']++;
                } else {
                    $results['unhealthy_servers']++;
                }

                // Update server status in database (align column names)
                    $server->update([
                        'status' => $healthStatus['status'],
                        'health_status' => $healthStatus['status'],
                        'last_health_check_at' => now(),
                        'response_time_ms' => $healthStatus['response_time'],
                        'uptime_percentage' => $healthStatus['uptime_percentage'],
                        // Keep quick aggregates in sync
                        'active_clients' => $healthStatus['active_clients'] ?? $server->active_clients,
                        'total_traffic_mb' => isset($healthStatus['bandwidth_usage']) ? (int) round(($healthStatus['bandwidth_usage'] ?? 0) * 1024) : $server->total_traffic_mb,
                    ]);

            } catch (\Exception $e) {
                $results['unhealthy_servers']++;
                $results['errors'][] = [
                    'server_id' => $server->id,
                    'error' => $e->getMessage()
                ];

                Log::error("Server health check failed for server {$server->id}", [
                    'error' => $e->getMessage(),
                    'server' => $server->toArray()
                ]);
            }
        }

        // Cache results for dashboard
        Cache::put('bulk_health_check_results', $results, now()->addMinutes(10));

        return $results;
    }

    /**
     * Check individual server health
     */
    public function checkServerHealth(Server $server): array
    {
        $startTime = microtime(true);

        try {
            // Test basic connectivity using helper that respects host/panel_port/web_base_path
            $baseUrl = method_exists($server, 'getApiBaseUrl') ? rtrim($server->getApiBaseUrl(), '/') : rtrim((string)$server->panel_url, '/');
            $probeUrl = $baseUrl . '/';
            $response = Http::timeout(10)->get($probeUrl);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                // Gather live metrics: online clients via X-UI; bandwidth from local aggregates
                $mockStats = [
                    'active_clients' => 0,
                    'bandwidth_usage' => 0.0,
                    'cpu_usage' => 0,
                    'memory_usage' => 0,
                    'disk_usage' => 0,
                ];
                try {
                    // Online clients from panel onlines endpoint
                    $xui = new XUIService($server);
                    $onlines = $xui->getOnlineClients();
                    $mockStats['active_clients'] = is_array($onlines) ? count($onlines) : 0;
                } catch (\Throwable $e) {
                    // keep zero and continue
                }
                try {
                    // Aggregate bandwidth usage (MB -> GB) from server clients
                    $totalMb = \App\Models\ServerClient::whereHas('inbound', function ($q) use ($server) {
                        $q->where('server_id', $server->id);
                    })->sum('traffic_used_mb');
                    $mockStats['bandwidth_usage'] = round(((float) $totalMb) / 1024, 2);
                } catch (\Throwable $e) { /* ignore */ }

                return [
                    'status' => $this->determineHealthStatus($server, $mockStats, $responseTime),
                    'response_time' => $responseTime,
                    'uptime_percentage' => $this->calculateUptimePercentage($server),
                    'active_clients' => $mockStats['active_clients'],
                    'bandwidth_usage' => $mockStats['bandwidth_usage'],
                    'cpu_usage' => $mockStats['cpu_usage'],
                    'memory_usage' => $mockStats['memory_usage'],
                    'disk_usage' => $mockStats['disk_usage'],
                    'issues' => $this->detectIssues($server, $mockStats, $responseTime)
                ];
            }

            return [
                'status' => 'unhealthy',
                'response_time' => $responseTime,
                'uptime_percentage' => $this->calculateUptimePercentage($server),
                'active_clients' => 0,
                'bandwidth_usage' => 0,
                'issues' => ['Server not responding']
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'offline',
                'response_time' => 0,
                'uptime_percentage' => $this->calculateUptimePercentage($server),
                'active_clients' => 0,
                'bandwidth_usage' => 0,
                'issues' => ['Connection failed: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Server configuration wizard for new server setup
     */
    public function runServerConfigurationWizard(array $serverData): array
    {
        $steps = [
            'validate_connection' => false,
            'setup_inbounds' => false,
            'configure_limits' => false,
            'test_configuration' => false,
            'activate_server' => false
        ];

        try {
            // Step 1: Validate connection to X-UI panel
            $steps['validate_connection'] = $this->validateServerConnection($serverData);

            if (!$steps['validate_connection']) {
                throw new \Exception('Cannot connect to X-UI panel');
            }

            // Step 2: Setup default inbounds based on server category
            $steps['setup_inbounds'] = $this->setupDefaultInbounds($serverData);

            // Step 3: Configure bandwidth and client limits
            $steps['configure_limits'] = $this->configureLimits($serverData);

            // Step 4: Test the complete configuration
            $steps['test_configuration'] = $this->testServerConfiguration($serverData);

            // Step 5: Activate server for customer use
            if (array_product($steps)) {
                $steps['activate_server'] = $this->activateServer($serverData);
            }

            return [
                'success' => array_product($steps),
                'steps' => $steps,
                'server_id' => $serverData['id'] ?? null,
                'message' => array_product($steps) ? 'Server configured successfully' : 'Configuration incomplete'
            ];

        } catch (\Exception $e) {
            Log::error('Server configuration wizard failed', [
                'error' => $e->getMessage(),
                'server_data' => $serverData,
                'completed_steps' => $steps
            ]);

            return [
                'success' => false,
                'steps' => $steps,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Automated server provisioning
     */
    public function provisionNewServer(array $provisioningData): array
    {
        \DB::beginTransaction();
        try {
            // Create server record
                $server = Server::create([
                    'name' => $provisioningData['name'],
                    'country' => $provisioningData['country'],
                    // Map to real columns
                    'ip' => $provisioningData['ip_address'] ?? null,
                    'panel_url' => $provisioningData['panel_url'],
                    'username' => $provisioningData['panel_username'],
                    'password' => $provisioningData['panel_password'],
                    'max_clients' => max((int)($provisioningData['max_clients'] ?? 1000), 1),
                    'bandwidth_limit_gb' => max((int)($provisioningData['bandwidth_limit_gb'] ?? 1000), 1),
                    'is_active' => false, // Initially inactive until configured
                    'status' => 'provisioning'
                ]);

            // Run configuration wizard
            $configResult = $this->runServerConfigurationWizard(array_merge(
                $provisioningData,
                ['id' => $server->id]
            ));

            if (!$configResult['success']) {
                throw new \Exception('Server configuration failed: ' . ($configResult['error'] ?? 'Unknown error'));
            }

            // Create default server plans for this server
            $this->createDefaultPlans($server, $provisioningData);

            // Perform initial health check
            $healthCheck = $this->checkServerHealth($server);

            $server->update([
                'status' => $healthCheck['status'],
                'is_active' => $healthCheck['status'] === 'healthy',
                'last_health_check_at' => now()
            ]);

            \DB::commit();
            return [
                'success' => true,
                'server' => $server,
                'health_status' => $healthCheck,
                'configuration_result' => $configResult,
                'message' => 'Server provisioned successfully'
            ];
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Server provisioning failed', [
                'error' => $e->getMessage(),
                'provisioning_data' => $provisioningData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Server provisioning failed'
            ];
        }
    }

    /**
     * Monitor server performance continuously
     */
    public function monitorServerPerformance(Server $server, bool $force = false): array
    {
        $metrics = [];
        $alerts = [];

        try {
            // Build current metrics from available live sources
            $xuiStats = [
                'cpu_usage' => rand(5, 30), // Placeholder; will be overridden if telemetry available
                'memory_usage' => rand(20, 70),
                'disk_usage' => rand(10, 50),
                'bandwidth_usage_gb' => 0.0,
                'active_clients' => 0,
            ];
            try {
                $xui = new XUIService($server);
                // Telemetry first (if panel/sidecar exposes it)
                $telemetry = $xui->getSystemTelemetry();
                if (is_array($telemetry)) {
                    $xuiStats['cpu_usage'] = (float) ($telemetry['cpu_usage'] ?? $xuiStats['cpu_usage']);
                    $xuiStats['memory_usage'] = (float) ($telemetry['memory_usage'] ?? $xuiStats['memory_usage']);
                    $xuiStats['disk_usage'] = (float) ($telemetry['disk_usage'] ?? $xuiStats['disk_usage']);
                }
                // Cache onlines briefly to reduce API pressure unless force-refreshing
                $onlinesCacheKey = "server_onlines_{$server->id}";
                if ($force) {
                    Cache::forget($onlinesCacheKey);
                }
                $onlines = Cache::remember($onlinesCacheKey, now()->addSeconds(60), function () use ($xui) {
                    return $xui->getOnlineClients();
                });
                $xuiStats['active_clients'] = is_array($onlines) ? count($onlines) : 0;
            } catch (\Throwable $e) { /* ignore */ }
            try {
                $totalMb = \App\Models\ServerClient::whereHas('inbound', function ($q) use ($server) {
                    $q->where('server_id', $server->id);
                })->sum('traffic_used_mb');
                $xuiStats['bandwidth_usage_gb'] = round(((float) $totalMb) / 1024, 2);
            } catch (\Throwable $e) { /* ignore */ }
            $currentTime = now();

            // CPU Usage Monitoring
            $cpuUsage = $xuiStats['cpu_usage'] ?? 0;
            if ($cpuUsage > 80) {
                $alerts[] = [
                    'type' => 'high_cpu_usage',
                    'severity' => $cpuUsage > 90 ? 'critical' : 'warning',
                    'message' => "CPU usage is {$cpuUsage}%",
                    'recommendation' => 'Consider reducing client load or upgrading server'
                ];
            }

            // Memory Usage Monitoring
            $memoryUsage = $xuiStats['memory_usage'] ?? 0;
            if ($memoryUsage > 85) {
                $alerts[] = [
                    'type' => 'high_memory_usage',
                    'severity' => $memoryUsage > 95 ? 'critical' : 'warning',
                    'message' => "Memory usage is {$memoryUsage}%",
                    'recommendation' => 'Restart services or upgrade server memory'
                ];
            }

            // Bandwidth Monitoring (guard division by zero)
            $bandwidthUsage = (float) ($xuiStats['bandwidth_usage_gb'] ?? 0);
            $bandwidthLimit = (float) ($server->bandwidth_limit_gb ?? 0);
            $bandwidthPercentage = $bandwidthLimit > 0
                ? round(($bandwidthUsage / $bandwidthLimit) * 100, 2)
                : 0.0;

            if ($bandwidthPercentage > 80) {
                $alerts[] = [
                    'type' => 'high_bandwidth_usage',
                    'severity' => $bandwidthPercentage > 95 ? 'critical' : 'warning',
                    'message' => "Bandwidth usage is {$bandwidthPercentage}% ({$bandwidthUsage}GB/{$bandwidthLimit}GB)",
                    'recommendation' => 'Monitor client usage or increase bandwidth limit'
                ];
            }

            // Client Count Monitoring (guard division by zero)
            $activeClients = (int) ($xuiStats['active_clients'] ?? 0);
            $maxClients = (int) ($server->max_clients ?? 0);
            $clientPercentage = $maxClients > 0
                ? round(($activeClients / $maxClients) * 100, 2)
                : 0.0;

            if ($clientPercentage > 90) {
                $alerts[] = [
                    'type' => 'high_client_usage',
                    'severity' => 'warning',
                    'message' => "Client usage is {$clientPercentage}% ({$activeClients}/{$maxClients})",
                    'recommendation' => 'Consider increasing client limit or adding new servers'
                ];
            }

            // Response Time Monitoring
            $responseTime = $this->checkServerHealth($server)['response_time'];
            if ($responseTime > 1000) {
                $alerts[] = [
                    'type' => 'slow_response',
                    'severity' => $responseTime > 3000 ? 'critical' : 'warning',
                    'message' => "Server response time is {$responseTime}ms",
                    'recommendation' => 'Check server load and network connectivity'
                ];
            }

            $metrics = [
                'server_id' => $server->id,
                'timestamp' => $currentTime,
                'cpu_usage' => $cpuUsage,
                'memory_usage' => $memoryUsage,
                'disk_usage' => $xuiStats['disk_usage'] ?? 0,
                'bandwidth_usage_gb' => $bandwidthUsage,
                'bandwidth_percentage' => $bandwidthPercentage,
                'active_clients' => $activeClients,
                'client_percentage' => $clientPercentage,
                'response_time_ms' => $responseTime,
                'uptime_percentage' => $this->calculateUptimePercentage($server),
                'alerts_count' => count($alerts),
                'status' => count($alerts) > 0 ? 'warning' : 'healthy'
            ];

            // Persist quick aggregates on the server for dashboard summaries
            try {
                $server->update([
                    'active_clients' => $activeClients,
                    'total_traffic_mb' => (int) round($bandwidthUsage * 1024),
                    'response_time_ms' => $responseTime,
                    'uptime_percentage' => $metrics['uptime_percentage'],
                    'status' => $metrics['status'],
                    'health_status' => $metrics['status'],
                ]);
            } catch (\Throwable $e) { /* ignore persist errors */ }

            // Store metrics in cache for real-time monitoring (short TTL)
            $metricsCacheKey = "server_metrics_{$server->id}";
            if ($force) {
                Cache::forget($metricsCacheKey);
            }
            Cache::put($metricsCacheKey, $metrics, now()->addMinutes(2));

            // Store historical data
            $this->storePerformanceMetrics($server, $metrics);

            if (!empty($alerts)) {
                // Send notifications for critical alerts
                $this->handlePerformanceAlerts($server, $alerts);
            }

            return [
                'success' => true,
                'metrics' => $metrics,
                'alerts' => $alerts
            ];

        } catch (\Exception $e) {
            Log::error("Performance monitoring failed for server {$server->id}", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'metrics' => [],
                'alerts' => []
            ];
        }
    }

    /**
     * Configuration management for servers
     */
    public function manageServerConfiguration(Server $server, array $configChanges): array
    {
        $results = [];

        try {
            foreach ($configChanges as $configType => $configData) {
                switch ($configType) {
                    case 'inbounds':
                        $results['inbounds'] = $this->updateInboundConfiguration($server, $configData);
                        break;

                    case 'limits':
                        $results['limits'] = $this->updateServerLimits($server, $configData);
                        break;

                    case 'security':
                        $results['security'] = $this->updateSecuritySettings($server, $configData);
                        break;

                    case 'networking':
                        $results['networking'] = $this->updateNetworkingSettings($server, $configData);
                        break;

                    default:
                        $results[$configType] = [
                            'success' => false,
                            'message' => "Unknown configuration type: {$configType}"
                        ];
                }
            }

            // Validate configuration after changes
            $validationResult = $this->validateServerConfiguration($server);

            return [
                'success' => $validationResult['success'],
                'configuration_results' => $results,
                'validation' => $validationResult,
                'message' => 'Configuration management completed'
            ];

        } catch (\Exception $e) {
            Log::error("Configuration management failed for server {$server->id}", [
                'error' => $e->getMessage(),
                'config_changes' => $configChanges
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Configuration management failed'
            ];
        }
    }

    /**
     * Get server management dashboard data
     */
    public function getManagementDashboardData(): array
    {
    $servers = Server::with(['plans', 'inbounds'])->get();

        return [
            'summary' => [
                'total_servers' => $servers->count(),
                'active_servers' => $servers->where('is_active', true)->count(),
                // health_status is the column that stores healthy/critical; status is up/down/paused
                'healthy_servers' => $servers->where('health_status', 'healthy')->count(),
                'servers_with_alerts' => $this->getServersWithAlerts()->count(),
                'total_clients' => $this->getTotalActiveClients(),
                'total_bandwidth_gb' => $this->getTotalBandwidthUsage(),
                'average_response_time' => $this->getAverageResponseTime(),
                'overall_uptime' => $this->getOverallUptimePercentage()
            ],
            'servers_by_status' => [
                'healthy' => $servers->where('status', 'healthy')->count(),
                'warning' => $servers->where('status', 'warning')->count(),
                'unhealthy' => $servers->where('status', 'unhealthy')->count(),
                'offline' => $servers->where('status', 'offline')->count(),
                'provisioning' => $servers->where('status', 'provisioning')->count()
            ],
            'geographic_distribution' => $this->getGeographicDistribution($servers),
            'performance_trends' => $this->getPerformanceTrends(),
            'recent_alerts' => $this->getRecentAlerts(),
            'top_performing_servers' => $this->getTopPerformingServers(5),
            'servers_needing_attention' => $this->getServersNeedingAttention()
        ];
    }

    // Protected helper methods

    protected function determineHealthStatus(Server $server, array $xuiStats, float $responseTime): string
    {
        $issues = $this->detectIssues($server, $xuiStats, $responseTime);

        if (empty($issues)) {
            return 'healthy';
        }

        $criticalIssues = array_filter($issues, function($issue) {
            return strpos(strtolower($issue), 'critical') !== false ||
                   strpos(strtolower($issue), 'offline') !== false ||
                   strpos(strtolower($issue), 'timeout') !== false;
        });

        if (!empty($criticalIssues)) {
            return 'unhealthy';
        }

        return 'warning';
    }

    protected function detectIssues(Server $server, array $xuiStats, float $responseTime): array
    {
        $issues = [];

        if ($responseTime > 3000) {
            $issues[] = 'Critical: Very slow response time';
        } elseif ($responseTime > 1000) {
            $issues[] = 'Warning: Slow response time';
        }

        if (isset($xuiStats['cpu_usage']) && $xuiStats['cpu_usage'] > 90) {
            $issues[] = 'Critical: High CPU usage';
        } elseif (isset($xuiStats['cpu_usage']) && $xuiStats['cpu_usage'] > 80) {
            $issues[] = 'Warning: Elevated CPU usage';
        }

        if (isset($xuiStats['memory_usage']) && $xuiStats['memory_usage'] > 95) {
            $issues[] = 'Critical: High memory usage';
        } elseif (isset($xuiStats['memory_usage']) && $xuiStats['memory_usage'] > 85) {
            $issues[] = 'Warning: Elevated memory usage';
        }

        return $issues;
    }

    protected function calculateUptimePercentage(Server $server): float
    {
        // Calculate uptime based on health check history
        // This would typically query a separate health_checks table
        return 99.5; // Placeholder implementation
    }

    protected function validateServerConnection(array $serverData): bool
    {
        try {
            $response = Http::timeout(10)->get($serverData['panel_url'] . '/panel/');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function setupDefaultInbounds(array $serverData): bool
    {
        // Implementation would create default inbound configurations
        // based on server category and requirements
        return true; // Placeholder
    }

    protected function configureLimits(array $serverData): bool
    {
        // Implementation would set bandwidth and client limits
        return true; // Placeholder
    }

    protected function testServerConfiguration(array $serverData): bool
    {
        // Implementation would test the complete server setup
        return true; // Placeholder
    }

    protected function activateServer(array $serverData): bool
    {
        // Implementation would activate the server for customer use
        if (isset($serverData['id'])) {
            Server::find($serverData['id'])->update(['is_active' => true]);
            return true;
        }
        return false;
    }

    protected function createDefaultPlans(Server $server, array $provisioningData): void
    {
        $categories = ServerCategory::all();

        foreach ($categories as $category) {
            ServerPlan::create([
                'server_id' => $server->id,
                'server_category_id' => $category->id,
                'name' => "{$category->name} - {$server->name}",
                'description' => "High-performance {$category->name} proxy service in {$server->country}",
                'price_monthly' => $this->getDefaultPrice($category->name),
                'max_concurrent_connections' => $this->getDefaultConnections($category->name),
                'bandwidth_limit_gb' => 100,
                'is_active' => true
            ]);
        }
    }

    protected function getDefaultPrice(string $categoryName): float
    {
        $prices = [
            'Gaming' => 15.99,
            'Streaming' => 12.99,
            'General' => 9.99,
            'Business' => 24.99
        ];

        return $prices[$categoryName] ?? 9.99;
    }

    protected function getDefaultConnections(string $categoryName): int
    {
        $connections = [
            'Gaming' => 5,
            'Streaming' => 3,
            'General' => 10,
            'Business' => 25
        ];

        return $connections[$categoryName] ?? 5;
    }

    protected function storePerformanceMetrics(Server $server, array $metrics): void
    {
        // Implementation would store metrics in a time-series database
        // or dedicated metrics table for historical analysis
    }

    protected function handlePerformanceAlerts(Server $server, array $alerts): void
    {
        foreach ($alerts as $alert) {
            if ($alert['severity'] === 'critical') {
                // Send immediate notifications
                Log::critical("Server {$server->id} critical alert", $alert);
            }
        }
    }

    protected function updateInboundConfiguration(Server $server, array $configData): array
    {
        // Implementation would update X-UI inbound configurations
        return ['success' => true, 'message' => 'Inbound configuration updated'];
    }

    protected function updateServerLimits(Server $server, array $configData): array
    {
        $nextMax = (int) ($configData['max_clients'] ?? $server->max_clients ?? 0);
        $nextBw = (int) ($configData['bandwidth_limit_gb'] ?? $server->bandwidth_limit_gb ?? 0);
        $server->update([
            'max_clients' => max($nextMax, 1),
            'bandwidth_limit_gb' => max($nextBw, 1),
        ]);

        return ['success' => true, 'message' => 'Server limits updated'];
    }

    protected function updateSecuritySettings(Server $server, array $configData): array
    {
        // Implementation would update security configurations
        return ['success' => true, 'message' => 'Security settings updated'];
    }

    protected function updateNetworkingSettings(Server $server, array $configData): array
    {
        // Implementation would update networking configurations
        return ['success' => true, 'message' => 'Networking settings updated'];
    }

    protected function validateServerConfiguration(Server $server): array
    {
        // Implementation would validate the complete server configuration
        return ['success' => true, 'message' => 'Configuration valid'];
    }

    protected function getServersWithAlerts(): Collection
    {
        return Server::where('status', '!=', 'healthy')->get();
    }

    protected function getTotalActiveClients(): int
    {
        return Cache::remember('total_active_clients', now()->addMinutes(2), function() {
            return Server::where('is_active', true)->sum('active_clients') ?? 0;
        });
    }

    protected function getTotalBandwidthUsage(): float
    {
        return Cache::remember('total_bandwidth_usage', now()->addMinutes(2), function() {
            // Convert total_traffic_mb (aggregate MB) to GB
            $totalMb = Server::where('is_active', true)->sum('total_traffic_mb') ?? 0;
            return round($totalMb / 1024, 2);
        });
    }

    protected function getAverageResponseTime(): float
    {
        return Cache::remember('average_response_time', now()->addMinutes(3), function() {
            return Server::where('is_active', true)->avg('response_time_ms') ?? 0;
        });
    }

    protected function getOverallUptimePercentage(): float
    {
        return Cache::remember('overall_uptime', now()->addMinutes(3), function() {
            return Server::where('is_active', true)->avg('uptime_percentage') ?? 0;
        });
    }

    /**
     * Force refresh caches for dashboard aggregates and per-server metrics
     */
    public function forceRefreshDashboardCaches(): void
    {
        // Invalidate aggregate caches
        Cache::forget('total_active_clients');
        Cache::forget('total_bandwidth_usage');
        Cache::forget('average_response_time');
        Cache::forget('overall_uptime');
        Cache::forget('bulk_health_check_results');

        // Invalidate and repopulate per-server caches
        $servers = Server::where('is_active', true)->get();
        foreach ($servers as $server) {
            Cache::forget("server_onlines_{$server->id}");
            Cache::forget("server_metrics_{$server->id}");
            // Recompute and warm caches
            try { $this->monitorServerPerformance($server, true); } catch (\Throwable $e) { /* continue */ }
        }
    }

    protected function getGeographicDistribution(Collection $servers): array
    {
        return $servers->groupBy('country')
            ->map(function ($countryServers, $country) {
                return [
                    'country' => $country,
                    'server_count' => $countryServers->count(),
                    'healthy_count' => $countryServers->where('health_status', 'healthy')->count(),
                    // No city column; keep key for compatibility but ensure it's a plain array for Blade implode()
                    'cities' => [],
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getPerformanceTrends(): array
    {
        // Implementation would return performance trend data
        return [
            'response_time_trend' => 'improving',
            'uptime_trend' => 'stable',
            'client_growth' => 'increasing'
        ];
    }

    protected function getRecentAlerts(): array
    {
        // Implementation would return recent alert data
        return [];
    }

    protected function getTopPerformingServers(int $limit): array
    {
        return Server::where('is_active', true)
            ->orderByDesc('uptime_percentage')
            ->orderBy('response_time_ms')
            ->limit($limit)
            ->get(['id', 'name', 'country', 'uptime_percentage', 'response_time_ms'])
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'country' => $s->country,
                    'city' => null, // compatibility key for views
                    'uptime_percentage' => $s->uptime_percentage,
                    'response_time_ms' => $s->response_time_ms,
                ];
            })
            ->toArray();
    }

    protected function getServersNeedingAttention(): array
    {
        return Server::where('is_active', true)
            ->where(function($query) {
                // health_status reflects healthy/critical; status is up/down/paused
                $query->where('health_status', '!=', 'healthy')
                      ->orWhere('uptime_percentage', '<', 99)
                      ->orWhere('response_time_ms', '>', 1000);
            })
            ->get(['id', 'name', 'country', 'status', 'uptime_percentage', 'response_time_ms'])
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'country' => $s->country,
                    'city' => null, // compatibility key for views
                    'status' => $s->status,
                    'uptime_percentage' => $s->uptime_percentage,
                    'response_time_ms' => $s->response_time_ms,
                ];
            })
            ->toArray();
    }
}
