<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Proxy Load Balancer Service
 *
 * Handles load balancing algorithms, health-aware routing, and traffic distribution.
 */
class ProxyLoadBalancer
{
    private $algorithms = [
        'round_robin',
        'weighted_round_robin',
        'least_connections',
        'ip_hash',
        'geographic',
        'performance_based'
    ];

    /**
     * Create a new load balancer instance
     */
    public function createLoadBalancer($customerId, $config): array
    {
        try {
            $loadBalancerId = uniqid('lb_' . $customerId . '_');

            $loadBalancerConfig = [
                'id' => $loadBalancerId,
                'customer_id' => $customerId,
                'algorithm' => $config['algorithm'] ?? 'weighted_round_robin',
                'health_check_enabled' => $config['health_check'] ?? true,
                'failover_enabled' => $config['failover'] ?? true,
                'sticky_sessions' => $config['sticky_sessions'] ?? false,
                'session_persistence' => $config['session_persistence'] ?? 'memory',
                'endpoints' => $this->prepareEndpoints($customerId, $config),
                'weights' => $this->calculateEndpointWeights($customerId),
                'health_thresholds' => [
                    'response_time_max' => $config['response_threshold'] ?? 2000,
                    'error_rate_max' => $config['error_threshold'] ?? 5,
                    'bandwidth_max' => $config['bandwidth_threshold'] ?? 80
                ],
                'traffic_distribution' => [
                    'method' => $config['distribution_method'] ?? 'equal',
                    'custom_ratios' => $config['custom_ratios'] ?? []
                ],
                'monitoring' => [
                    'metrics_enabled' => true,
                    'detailed_logging' => $config['detailed_logging'] ?? false,
                    'real_time_analytics' => $config['real_time_analytics'] ?? true
                ],
                'created_at' => now()->toISOString(),
                'status' => 'active'
            ];

            // Store load balancer configuration
            $this->storeLoadBalancerConfig($loadBalancerId, $loadBalancerConfig);

            // Initialize routing tables
            $this->initializeRoutingTables($loadBalancerId, $loadBalancerConfig);

            // Setup health monitoring
            $this->setupLoadBalancerHealthMonitoring($loadBalancerId);

            Log::info("Load balancer created", [
                'load_balancer_id' => $loadBalancerId,
                'customer_id' => $customerId,
                'algorithm' => $loadBalancerConfig['algorithm'],
                'endpoints' => count($loadBalancerConfig['endpoints'])
            ]);

            return [
                'success' => true,
                'load_balancer_id' => $loadBalancerId,
                'config' => $loadBalancerConfig,
                'endpoints_count' => count($loadBalancerConfig['endpoints'])
            ];
        } catch (\Exception $e) {
            Log::error('Load balancer creation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Route request through load balancer
     */
    public function routeRequest($loadBalancerId, $requestInfo): array
    {
        try {
            $config = $this->getLoadBalancerConfig($loadBalancerId);

            if (!$config || $config['status'] !== 'active') {
                throw new \Exception("Load balancer not found or inactive: {$loadBalancerId}");
            }

            // Get healthy endpoints
            $healthyEndpoints = $this->getHealthyEndpoints($config);

            if (empty($healthyEndpoints)) {
                // Failover to backup endpoints if available
                $healthyEndpoints = $this->getFailoverEndpoints($config);

                if (empty($healthyEndpoints)) {
                    throw new \Exception("No healthy endpoints available");
                }
            }

            // Select endpoint based on algorithm
            $selectedEndpoint = $this->selectEndpoint($config, $healthyEndpoints, $requestInfo);

            // Update routing statistics
            $this->updateRoutingStatistics($loadBalancerId, $selectedEndpoint, $requestInfo);

            // Handle sticky sessions if enabled
            if ($config['sticky_sessions']) {
                $this->handleStickySession($loadBalancerId, $selectedEndpoint, $requestInfo);
            }

            return [
                'success' => true,
                'endpoint' => $selectedEndpoint,
                'load_balancer_id' => $loadBalancerId,
                'algorithm_used' => $config['algorithm'],
                'routing_time' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("Request routing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Select endpoint based on configured algorithm
     */
    private function selectEndpoint($config, $endpoints, $requestInfo): array
    {
        switch ($config['algorithm']) {
            case 'round_robin':
                return $this->roundRobinSelection($config['id'], $endpoints);

            case 'weighted_round_robin':
                return $this->weightedRoundRobinSelection($config, $endpoints);

            case 'least_connections':
                return $this->leastConnectionsSelection($endpoints);

            case 'ip_hash':
                return $this->ipHashSelection($endpoints, $requestInfo);

            case 'geographic':
                return $this->geographicSelection($endpoints, $requestInfo);

            case 'performance_based':
                return $this->performanceBasedSelection($endpoints);

            default:
                return $this->roundRobinSelection($config['id'], $endpoints);
        }
    }

    /**
     * Round Robin algorithm implementation
     */
    private function roundRobinSelection($loadBalancerId, $endpoints): array
    {
        $currentIndex = Cache::get("lb_rr_index_{$loadBalancerId}", 0);
        $selectedEndpoint = $endpoints[$currentIndex % count($endpoints)];

        // Update index for next request
        Cache::put("lb_rr_index_{$loadBalancerId}", ($currentIndex + 1) % count($endpoints), 3600);

        return $selectedEndpoint;
    }

    /**
     * Weighted Round Robin algorithm implementation
     */
    private function weightedRoundRobinSelection($config, $endpoints): array
    {
        $weights = $config['weights'] ?? [];
        $weightedEndpoints = [];

        foreach ($endpoints as $endpoint) {
            $weight = $weights[$endpoint['server_id']] ?? 100;
            $normalizedWeight = max(1, intval($weight / 10)); // Normalize to 1-10 range

            for ($i = 0; $i < $normalizedWeight; $i++) {
                $weightedEndpoints[] = $endpoint;
            }
        }

        $currentIndex = Cache::get("lb_wrr_index_{$config['id']}", 0);
        $selectedEndpoint = $weightedEndpoints[$currentIndex % count($weightedEndpoints)];

        Cache::put("lb_wrr_index_{$config['id']}", ($currentIndex + 1) % count($weightedEndpoints), 3600);

        return $selectedEndpoint;
    }

    /**
     * Least Connections algorithm implementation
     */
    private function leastConnectionsSelection($endpoints): array
    {
        $minConnections = PHP_INT_MAX;
        $selectedEndpoint = $endpoints[0];

        foreach ($endpoints as $endpoint) {
            $connections = $this->getActiveConnections($endpoint);

            if ($connections < $minConnections) {
                $minConnections = $connections;
                $selectedEndpoint = $endpoint;
            }
        }

        return $selectedEndpoint;
    }

    /**
     * IP Hash algorithm implementation
     */
    private function ipHashSelection($endpoints, $requestInfo): array
    {
        $clientIP = $requestInfo['client_ip'] ?? '127.0.0.1';
        $hash = crc32($clientIP);
        $index = abs($hash) % count($endpoints);

        return $endpoints[$index];
    }

    /**
     * Geographic-based selection
     */
    private function geographicSelection($endpoints, $requestInfo): array
    {
        $clientCountry = $requestInfo['country'] ?? 'US';

        // Find endpoints in the same region
        $regionalEndpoints = array_filter($endpoints, function ($endpoint) use ($clientCountry) {
            return $endpoint['country'] === $clientCountry;
        });

        if (!empty($regionalEndpoints)) {
            return $regionalEndpoints[array_rand($regionalEndpoints)];
        }

        // Fallback to any available endpoint
        return $endpoints[array_rand($endpoints)];
    }

    /**
     * Performance-based selection
     */
    private function performanceBasedSelection($endpoints): array
    {
        $bestEndpoint = $endpoints[0];
        $bestScore = 0;

        foreach ($endpoints as $endpoint) {
            $performance = $this->getEndpointPerformanceScore($endpoint);

            if ($performance > $bestScore) {
                $bestScore = $performance;
                $bestEndpoint = $endpoint;
            }
        }

        return $bestEndpoint;
    }

    /**
     * Get load balancer performance metrics
     */
    public function getLoadBalancerMetrics($loadBalancerId): array
    {
        try {
            $config = $this->getLoadBalancerConfig($loadBalancerId);

            if (!$config) {
                throw new \Exception("Load balancer not found: {$loadBalancerId}");
            }

            $metrics = [
                'requests_per_second' => $this->getRequestsPerSecond($loadBalancerId),
                'response_times' => $this->getResponseTimeMetrics($loadBalancerId),
                'error_rates' => $this->getErrorRateMetrics($loadBalancerId),
                'endpoint_distribution' => $this->getEndpointDistribution($loadBalancerId),
                'health_status' => $this->getEndpointHealthStatus($config),
                'throughput' => $this->getThroughputMetrics($loadBalancerId),
                'connection_metrics' => $this->getConnectionMetrics($loadBalancerId),
                'algorithm_efficiency' => $this->getAlgorithmEfficiency($loadBalancerId)
            ];

            return [
                'success' => true,
                'load_balancer_id' => $loadBalancerId,
                'metrics' => $metrics,
                'generated_at' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("Load balancer metrics error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update load balancer configuration
     */
    public function updateLoadBalancerConfig($loadBalancerId, $updates): array
    {
        try {
            $config = $this->getLoadBalancerConfig($loadBalancerId);

            if (!$config) {
                throw new \Exception("Load balancer not found: {$loadBalancerId}");
            }

            // Merge updates with existing configuration
            $updatedConfig = array_merge($config, $updates);
            $updatedConfig['updated_at'] = now()->toISOString();

            // Validate configuration
            $this->validateLoadBalancerConfig($updatedConfig);

            // Store updated configuration
            $this->storeLoadBalancerConfig($loadBalancerId, $updatedConfig);

            // Reinitialize routing tables if algorithm changed
            if (isset($updates['algorithm']) && $updates['algorithm'] !== $config['algorithm']) {
                $this->initializeRoutingTables($loadBalancerId, $updatedConfig);
            }

            Log::info("Load balancer configuration updated", [
                'load_balancer_id' => $loadBalancerId,
                'updates' => array_keys($updates)
            ]);

            return [
                'success' => true,
                'load_balancer_id' => $loadBalancerId,
                'config' => $updatedConfig
            ];
        } catch (\Exception $e) {
            Log::error("Load balancer config update error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Private helper methods

    private function prepareEndpoints($customerId, $config): array
    {
        $customer = \App\Models\Customer::find($customerId);
        if (!$customer) return [];

        return $customer->orders()
            ->where('payment_status', 'paid')
            ->where('status', 'active')
            ->with(['serverPlan.server'])
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'server_id' => $order->serverPlan->server->id,
                    'endpoint' => $order->serverPlan->server->ip_address . ':' . $order->serverPlan->port,
                    'protocol' => $order->serverPlan->protocol ?? 'vless',
                    'country' => $order->serverPlan->server->country ?? 'US',
                    'region' => $order->serverPlan->server->region ?? 'us-east-1',
                    'status' => 'healthy',
                    'weight' => 100,
                    'max_connections' => $order->serverPlan->max_connections ?? 1000
                ];
            })->toArray();
    }

    private function calculateEndpointWeights($customerId): array
    {
        $servers = Server::whereHas('serverPlans.orders', function ($query) use ($customerId) {
            $query->where('customer_id', $customerId)
                  ->where('payment_status', 'paid')
                  ->where('status', 'active');
        })->get();

        $weights = [];
        foreach ($servers as $server) {
            $weight = 100;

            // Adjust weight based on server performance
            if ($server->cpu_usage > 80) $weight -= 30;
            if ($server->memory_usage > 85) $weight -= 20;
            if ($server->uptime_percentage < 95) $weight -= 25;

            $weights[$server->id] = max($weight, 10);
        }

        return $weights;
    }

    private function getHealthyEndpoints($config): array
    {
        return array_filter($config['endpoints'], function ($endpoint) {
            return $endpoint['status'] === 'healthy';
        });
    }

    private function getFailoverEndpoints($config): array
    {
        // Return endpoints with warning status as fallback
        return array_filter($config['endpoints'], function ($endpoint) {
            return in_array($endpoint['status'], ['healthy', 'warning']);
        });
    }

    private function storeLoadBalancerConfig($id, $config): void
    {
        Cache::put("load_balancer_{$id}", $config, 86400);
    }

    private function getLoadBalancerConfig($id): ?array
    {
        return Cache::get("load_balancer_{$id}");
    }

    private function initializeRoutingTables($id, $config): void
    {
        // Initialize algorithm-specific routing tables
        Cache::put("lb_rr_index_{$id}", 0, 3600);
        Cache::put("lb_wrr_index_{$id}", 0, 3600);
        Cache::put("lb_routing_stats_{$id}", [], 86400);
    }

    private function setupLoadBalancerHealthMonitoring($id): void
    {
        // Setup health monitoring for the load balancer
        Cache::put("lb_health_monitor_{$id}", [
            'enabled' => true,
            'check_interval' => 60,
            'last_check' => now()->toISOString()
        ], 86400);
    }

    private function updateRoutingStatistics($id, $endpoint, $requestInfo): void
    {
        $stats = Cache::get("lb_routing_stats_{$id}", []);
        $serverId = $endpoint['server_id'];

        if (!isset($stats[$serverId])) {
            $stats[$serverId] = ['requests' => 0, 'last_request' => null];
        }

        $stats[$serverId]['requests']++;
        $stats[$serverId]['last_request'] = now()->toISOString();

        Cache::put("lb_routing_stats_{$id}", $stats, 86400);
    }

    private function handleStickySession($id, $endpoint, $requestInfo): void
    {
        $sessionId = $requestInfo['session_id'] ?? $requestInfo['client_ip'] ?? uniqid();
        Cache::put("lb_sticky_{$id}_{$sessionId}", $endpoint['server_id'], 1800); // 30 minutes
    }

    private function validateLoadBalancerConfig($config): void
    {
        if (!in_array($config['algorithm'], $this->algorithms)) {
            throw new \Exception("Invalid load balancing algorithm: {$config['algorithm']}");
        }

        if (empty($config['endpoints'])) {
            throw new \Exception("Load balancer must have at least one endpoint");
        }
    }

    // Mock implementations for complex metrics
    private function getActiveConnections($endpoint): int { return rand(10, 100); }
    private function getEndpointPerformanceScore($endpoint): int { return rand(70, 100); }
    private function getRequestsPerSecond($id): float { return rand(50, 500) / 10; }
    private function getResponseTimeMetrics($id): array { return ['avg' => rand(100, 300), 'p95' => rand(200, 500)]; }
    private function getErrorRateMetrics($id): array { return ['rate' => rand(0, 5)]; }
    private function getEndpointDistribution($id): array { return ['server_1' => 30, 'server_2' => 70]; }
    private function getEndpointHealthStatus($config): array { return ['healthy' => count($config['endpoints']), 'unhealthy' => 0]; }
    private function getThroughputMetrics($id): array { return ['mbps' => rand(100, 1000)]; }
    private function getConnectionMetrics($id): array { return ['active' => rand(50, 200), 'total' => rand(1000, 5000)]; }
    private function getAlgorithmEfficiency($id): array { return ['score' => rand(85, 98)]; }
}
