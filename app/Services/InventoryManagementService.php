<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerClient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Events\SystemAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InventoryManagementService
{
    /**
     * Check server capacity and availability
     */
    public function checkServerCapacity(Server $server): array
    {
        $activeClients = ServerClient::where('server_id', $server->id)
            ->where('is_active', true)
            ->count();

        $totalCapacity = $server->max_clients ?? 1000;
        $usedCapacity = $activeClients;
        $availableCapacity = $totalCapacity - $usedCapacity;
        $utilizationRate = $totalCapacity > 0 ? ($usedCapacity / $totalCapacity) * 100 : 0;

        return [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'total_capacity' => $totalCapacity,
            'used_capacity' => $usedCapacity,
            'available_capacity' => $availableCapacity,
            'utilization_rate' => round($utilizationRate, 2),
            'status' => $this->getCapacityStatus($utilizationRate),
            'is_available' => $availableCapacity > 0,
        ];
    }

    /**
     * Get inventory status for all servers
     */
    public function getInventoryStatus(): array
    {
        $servers = Server::where('is_active', true)->get();
        $inventory = [];

        foreach ($servers as $server) {
            $capacity = $this->checkServerCapacity($server);
            $inventory[] = array_merge($capacity, [
                'server_info' => [
                    'location' => $server->location,
                    'country' => $server->country,
                    'bandwidth' => $server->bandwidth,
                    'protocols' => $server->protocols,
                ],
                'recent_orders' => $this->getRecentOrders($server->id),
                'performance_metrics' => $this->getServerPerformanceMetrics($server->id),
            ]);
        }

        return $inventory;
    }

    /**
     * Reserve capacity for an order
     */
    public function reserveCapacity(Order $order): array
    {
        $reservations = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($order->orderItems as $item) {
                $server = Server::findOrFail($item->server_id);
                $capacity = $this->checkServerCapacity($server);
                
                if (!$capacity['is_available'] || $capacity['available_capacity'] < $item->quantity) {
                    throw new \Exception("Insufficient capacity on server {$server->name}");
                }
                
                // Create reservation record
                $reservation = [
                    'order_id' => $order->id,
                    'server_id' => $server->id,
                    'reserved_capacity' => $item->quantity,
                    'expires_at' => now()->addHours(24), // Reservation expires in 24 hours
                    'created_at' => now(),
                ];
                
                // Store reservation in cache
                $this->storeReservation($reservation);
                
                $reservations[] = $reservation;
                
                Log::info('Capacity reserved', [
                    'order_id' => $order->id,
                    'server_id' => $server->id,
                    'quantity' => $item->quantity,
                ]);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'reservations' => $reservations,
                'message' => 'Capacity reserved successfully',
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Failed to reserve capacity', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Release capacity reservation
     */
    public function releaseReservation(Order $order): void
    {
        $key = "capacity_reservations:{$order->id}";
        Cache::forget($key);
        
        Log::info('Capacity reservation released', [
            'order_id' => $order->id,
        ]);
    }

    /**
     * Auto-scale servers based on demand
     */
    public function autoScaleServers(): array
    {
        $servers = Server::where('is_active', true)->get();
        $scalingActions = [];

        foreach ($servers as $server) {
            $capacity = $this->checkServerCapacity($server);
            $action = $this->determineScalingAction($capacity);

            if ($action) {
                $scalingActions[] = [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'action' => $action,
                    'current_utilization' => $capacity['utilization_rate'],
                    'recommendation' => $this->getScalingRecommendation($capacity),
                ];

                $this->executeScalingAction($server, $action);
            }
        }

        return $scalingActions;
    }

    /**
     * Predict capacity needs
     */
    public function predictCapacityNeeds(int $days = 30): array
    {
        $historicalData = $this->getHistoricalUsageData($days);
        $predictions = [];

        foreach (Server::where('is_active', true)->get() as $server) {
            $serverData = $historicalData->where('server_id', $server->id);
            $prediction = $this->calculateCapacityPrediction($serverData);
            
            $predictions[] = [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'current_usage' => $this->checkServerCapacity($server),
                'predicted_usage' => $prediction,
                'recommendation' => $this->getCapacityRecommendation($prediction),
            ];
        }

        return $predictions;
    }

    /**
     * Get low capacity alerts
     */
    public function getLowCapacityAlerts(): array
    {
        $alerts = [];
        $servers = Server::where('is_active', true)->get();

        foreach ($servers as $server) {
            $capacity = $this->checkServerCapacity($server);
            
            if ($capacity['utilization_rate'] > 90) {
                $alerts[] = [
                    'severity' => 'critical',
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'utilization_rate' => $capacity['utilization_rate'],
                    'available_capacity' => $capacity['available_capacity'],
                    'message' => "Server {$server->name} is at {$capacity['utilization_rate']}% capacity",
                ];
            } elseif ($capacity['utilization_rate'] > 75) {
                $alerts[] = [
                    'severity' => 'warning',
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'utilization_rate' => $capacity['utilization_rate'],
                    'available_capacity' => $capacity['available_capacity'],
                    'message' => "Server {$server->name} is at {$capacity['utilization_rate']}% capacity",
                ];
            }
        }

        return $alerts;
    }

    /**
     * Rebalance load across servers
     */
    public function rebalanceLoad(): array
    {
        $servers = Server::where('is_active', true)->get();
        $rebalanceActions = [];

        // Find overloaded and underloaded servers
        $overloaded = [];
        $underloaded = [];

        foreach ($servers as $server) {
            $capacity = $this->checkServerCapacity($server);
            
            if ($capacity['utilization_rate'] > 80) {
                $overloaded[] = ['server' => $server, 'capacity' => $capacity];
            } elseif ($capacity['utilization_rate'] < 40) {
                $underloaded[] = ['server' => $server, 'capacity' => $capacity];
            }
        }

        // Suggest client migrations
        foreach ($overloaded as $overloadedServer) {
            foreach ($underloaded as $underloadedServer) {
                $migrationSuggestion = $this->calculateMigrationSuggestion(
                    $overloadedServer['server'],
                    $underloadedServer['server']
                );

                if ($migrationSuggestion['feasible']) {
                    $rebalanceActions[] = $migrationSuggestion;
                }
            }
        }

        return $rebalanceActions;
    }

    /**
     * Get server performance metrics
     */
    private function getServerPerformanceMetrics(int $serverId): array
    {
        $metrics = Cache::remember("server_metrics:{$serverId}", 300, function () use ($serverId) {
            return [
                'average_response_time' => $this->calculateAverageResponseTime($serverId),
                'uptime_percentage' => $this->calculateUptime($serverId),
                'success_rate' => $this->calculateSuccessRate($serverId),
                'traffic_volume' => $this->calculateTrafficVolume($serverId),
            ];
        });

        return $metrics;
    }

    /**
     * Get recent orders for a server
     */
    private function getRecentOrders(int $serverId): array
    {
        return OrderItem::where('server_id', $serverId)
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            })
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'order_id' => $item->order->id,
                    'quantity' => $item->quantity,
                    'created_at' => $item->created_at,
                    'status' => $item->order->order_status,
                ];
            })
            ->toArray();
    }

    /**
     * Get capacity status based on utilization rate
     */
    private function getCapacityStatus(float $utilizationRate): string
    {
        if ($utilizationRate >= 90) {
            return 'critical';
        } elseif ($utilizationRate >= 75) {
            return 'warning';
        } elseif ($utilizationRate >= 50) {
            return 'normal';
        } else {
            return 'low';
        }
    }

    /**
     * Store capacity reservation
     */
    private function storeReservation(array $reservation): void
    {
        $key = "capacity_reservations:{$reservation['order_id']}";
        Cache::put($key, $reservation, now()->addHours(24));
    }

    /**
     * Determine scaling action needed
     */
    private function determineScalingAction(array $capacity): ?string
    {
        if ($capacity['utilization_rate'] > 85) {
            return 'scale_up';
        } elseif ($capacity['utilization_rate'] < 20) {
            return 'scale_down';
        }

        return null;
    }

    /**
     * Get scaling recommendation
     */
    private function getScalingRecommendation(array $capacity): string
    {
        if ($capacity['utilization_rate'] > 85) {
            return 'Consider adding more server capacity or distributing load';
        } elseif ($capacity['utilization_rate'] < 20) {
            return 'Consider consolidating clients or reducing server resources';
        }

        return 'No scaling action needed';
    }

    /**
     * Execute scaling action
     */
    private function executeScalingAction(Server $server, string $action): void
    {
        switch ($action) {
            case 'scale_up':
                $this->sendScalingAlert($server, 'scale_up');
                break;
            case 'scale_down':
                $this->sendScalingAlert($server, 'scale_down');
                break;
        }
    }

    /**
     * Send scaling alert
     */
    private function sendScalingAlert(Server $server, string $action): void
    {
        $message = $action === 'scale_up' 
            ? "Server {$server->name} needs scaling up due to high utilization"
            : "Server {$server->name} can be scaled down due to low utilization";

        event(new SystemAlert([
            'type' => 'scaling_alert',
            'message' => $message,
            'data' => [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'action' => $action,
            ],
        ]));
    }

    /**
     * Get historical usage data
     */
    private function getHistoricalUsageData(int $days): \Illuminate\Support\Collection
    {
        return DB::table('server_clients')
            ->select('server_id', DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as client_count'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('server_id', 'date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Calculate capacity prediction
     */
    private function calculateCapacityPrediction($serverData): array
    {
        $data = $serverData->pluck('client_count')->toArray();
        
        if (count($data) < 2) {
            return [
                'predicted_usage' => 0,
                'trend' => 'stable',
                'confidence' => 0,
            ];
        }

        $trend = $this->calculateTrend($data);
        $prediction = end($data) + $trend;

        return [
            'predicted_usage' => max(0, $prediction),
            'trend' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
            'confidence' => $this->calculateConfidence($data),
        ];
    }

    /**
     * Calculate trend
     */
    private function calculateTrend(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0;

        $x = range(1, $n);
        $y = $data;

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(function ($xi, $yi) { return $xi * $yi; }, $x, $y));
        $sumX2 = array_sum(array_map(function ($xi) { return $xi * $xi; }, $x));

        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    }

    /**
     * Calculate confidence
     */
    private function calculateConfidence(array $data): float
    {
        $variance = $this->calculateVariance($data);
        return max(0, 100 - sqrt($variance));
    }

    /**
     * Calculate variance
     */
    private function calculateVariance(array $data): float
    {
        $mean = array_sum($data) / count($data);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $data)) / count($data);

        return $variance;
    }

    /**
     * Get capacity recommendation
     */
    private function getCapacityRecommendation(array $prediction): string
    {
        if ($prediction['trend'] === 'increasing' && $prediction['confidence'] > 70) {
            return 'Prepare for capacity increase';
        } elseif ($prediction['trend'] === 'decreasing' && $prediction['confidence'] > 70) {
            return 'Consider capacity optimization';
        }

        return 'Monitor capacity trends';
    }

    /**
     * Calculate migration suggestion
     */
    private function calculateMigrationSuggestion(Server $fromServer, Server $toServer): array
    {
        $fromCapacity = $this->checkServerCapacity($fromServer);
        $toCapacity = $this->checkServerCapacity($toServer);

        $migrationCount = min(
            $fromCapacity['used_capacity'] * 0.2, // Migrate 20% of clients
            $toCapacity['available_capacity'] * 0.8 // Don't fill target server more than 80%
        );

        return [
            'from_server' => $fromServer->name,
            'to_server' => $toServer->name,
            'migration_count' => floor($migrationCount),
            'feasible' => $migrationCount > 0,
            'impact' => [
                'from_utilization_after' => $fromCapacity['utilization_rate'] - ($migrationCount / $fromCapacity['total_capacity'] * 100),
                'to_utilization_after' => $toCapacity['utilization_rate'] + ($migrationCount / $toCapacity['total_capacity'] * 100),
            ],
        ];
    }

    /**
     * Calculate average response time
     */
    private function calculateAverageResponseTime(int $serverId): float
    {
        // This would typically come from monitoring data
        return 125.5; // Placeholder
    }

    /**
     * Calculate uptime
     */
    private function calculateUptime(int $serverId): float
    {
        // This would typically come from monitoring data
        return 99.9; // Placeholder
    }

    /**
     * Calculate success rate
     */
    private function calculateSuccessRate(int $serverId): float
    {
        $total = OrderItem::where('server_id', $serverId)
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            })
            ->count();

        $successful = OrderItem::where('server_id', $serverId)
            ->whereHas('order', function ($query) {
                $query->where('created_at', '>=', now()->subDays(7))
                      ->where('order_status', 'completed');
            })
            ->count();

        return $total > 0 ? ($successful / $total) * 100 : 100;
    }

    /**
     * Calculate traffic volume
     */
    private function calculateTrafficVolume(int $serverId): float
    {
        return ServerClient::where('server_id', $serverId)
            ->where('created_at', '>=', now()->subDays(7))
            ->sum('total_traffic') / (1024 * 1024 * 1024); // Convert to GB
    }
}
