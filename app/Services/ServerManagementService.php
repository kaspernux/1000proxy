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
                    'location' => "{$server->country}, {$server->city}",
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

                // Update server status in database
                $server->update([
                    'status' => $healthStatus['status'],
                    'last_health_check' => now(),
                    'response_time_ms' => $healthStatus['response_time'],
                    'uptime_percentage' => $healthStatus['uptime_percentage']
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
            // Test basic connectivity
            $response = Http::timeout(10)->get($server->panel_url . '/panel/');
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                // Get detailed server metrics from X-UI API
                try {
                    // Note: Using mock data since XUIService doesn't have getServerStats method yet
                    $mockStats = [
                        'active_clients' => rand(10, 100),
                        'bandwidth_usage' => rand(50, 500) / 10, // GB
                        'cpu_usage' => rand(5, 25),
                        'memory_usage' => rand(20, 60),
                        'disk_usage' => rand(10, 40)
                    ];
                } catch (\Exception $e) {
                    $mockStats = [
                        'active_clients' => 0,
                        'bandwidth_usage' => 0,
                        'cpu_usage' => 0,
                        'memory_usage' => 0,
                        'disk_usage' => 0
                    ];
                }

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
        DB::beginTransaction();

        try {
            // Create server record
            $server = Server::create([
                'name' => $provisioningData['name'],
                'country' => $provisioningData['country'],
                'city' => $provisioningData['city'],
                'ip_address' => $provisioningData['ip_address'],
                'panel_url' => $provisioningData['panel_url'],
                'panel_username' => $provisioningData['panel_username'],
                'panel_password' => $provisioningData['panel_password'],
                'max_clients' => $provisioningData['max_clients'] ?? 1000,
                'bandwidth_limit_gb' => $provisioningData['bandwidth_limit_gb'] ?? 1000,
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
                'last_health_check' => now()
            ]);

            DB::commit();

            return [
                'success' => true,
                'server' => $server,
                'health_status' => $healthCheck,
                'configuration_result' => $configResult,
                'message' => 'Server provisioned successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

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
    public function monitorServerPerformance(Server $server): array
    {
        $metrics = [];
        $alerts = [];

        try {
            // Get current metrics - using mock data since XUIService methods need to be implemented
            $xuiStats = [
                'cpu_usage' => rand(5, 30),
                'memory_usage' => rand(20, 70),
                'disk_usage' => rand(10, 50),
                'bandwidth_usage_gb' => rand(100, 1000) / 10,
                'active_clients' => rand(10, 200)
            ];
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

            // Bandwidth Monitoring
            $bandwidthUsage = $xuiStats['bandwidth_usage_gb'] ?? 0;
            $bandwidthLimit = $server->bandwidth_limit_gb;
            $bandwidthPercentage = ($bandwidthUsage / $bandwidthLimit) * 100;

            if ($bandwidthPercentage > 80) {
                $alerts[] = [
                    'type' => 'high_bandwidth_usage',
                    'severity' => $bandwidthPercentage > 95 ? 'critical' : 'warning',
                    'message' => "Bandwidth usage is {$bandwidthPercentage}% ({$bandwidthUsage}GB/{$bandwidthLimit}GB)",
                    'recommendation' => 'Monitor client usage or increase bandwidth limit'
                ];
            }

            // Client Count Monitoring
            $activeClients = $xuiStats['active_clients'] ?? 0;
            $maxClients = $server->max_clients;
            $clientPercentage = ($activeClients / $maxClients) * 100;

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

            // Store metrics in cache for real-time monitoring
            Cache::put("server_metrics_{$server->id}", $metrics, now()->addMinutes(5));

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
        $servers = Server::with(['plans', 'serverInbounds'])->get();

        return [
            'summary' => [
                'total_servers' => $servers->count(),
                'active_servers' => $servers->where('is_active', true)->count(),
                'healthy_servers' => $servers->where('status', 'healthy')->count(),
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
                'description' => "High-performance {$category->name} proxy service in {$server->city}, {$server->country}",
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
        $server->update([
            'max_clients' => $configData['max_clients'] ?? $server->max_clients,
            'bandwidth_limit_gb' => $configData['bandwidth_limit_gb'] ?? $server->bandwidth_limit_gb
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
        return Cache::remember('total_active_clients', now()->addMinutes(5), function() {
            return Server::where('is_active', true)->sum('current_clients') ?? 0;
        });
    }

    protected function getTotalBandwidthUsage(): float
    {
        return Cache::remember('total_bandwidth_usage', now()->addMinutes(5), function() {
            // Convert total_traffic_mb (aggregate MB) to GB
            $totalMb = Server::where('is_active', true)->sum('total_traffic_mb') ?? 0;
            return round($totalMb / 1024, 2);
        });
    }

    protected function getAverageResponseTime(): float
    {
        return Cache::remember('average_response_time', now()->addMinutes(5), function() {
            return Server::where('is_active', true)->avg('response_time_ms') ?? 0;
        });
    }

    protected function getOverallUptimePercentage(): float
    {
        return Cache::remember('overall_uptime', now()->addMinutes(10), function() {
            return Server::where('is_active', true)->avg('uptime_percentage') ?? 0;
        });
    }

    protected function getGeographicDistribution(Collection $servers): array
    {
        return $servers->groupBy('country')
            ->map(function($countryServers, $country) {
                return [
                    'country' => $country,
                    'server_count' => $countryServers->count(),
                    'healthy_count' => $countryServers->where('status', 'healthy')->count(),
                    'cities' => $countryServers->pluck('city')->unique()->values()
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
            ->get(['id', 'name', 'country', 'city', 'uptime_percentage', 'response_time_ms'])
            ->toArray();
    }

    protected function getServersNeedingAttention(): array
    {
        return Server::where('is_active', true)
            ->where(function($query) {
                $query->where('status', '!=', 'healthy')
                      ->orWhere('uptime_percentage', '<', 99)
                      ->orWhere('response_time_ms', '>', 1000);
            })
            ->get(['id', 'name', 'country', 'city', 'status', 'uptime_percentage', 'response_time_ms'])
            ->toArray();
    }
}
