<?php

namespace App\Filament\Widgets;

use App\Models\Server;
use App\Services\XUIService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ServerHealthMonitoringWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            $this->getServerStatusStat(),
            $this->getActiveConnectionsStat(),
            $this->getServerLoadStat(),
            $this->getXUIConnectionsStat(),
            $this->getTotalTrafficStat(),
            $this->getServerPerformanceStat(),
        ];
    }

    private function getServerStatusStat(): Stat
    {
        $totalServers = Server::count();
        $upServers = Cache::remember('up_servers_count', 300, function () {
            return Server::where('status', 'up')->count();
        });
        $downServers = Cache::remember('down_servers_count', 300, function () {
            return Server::where('status', 'down')->count();
        });
        $pausedServers = Cache::remember('paused_servers_count', 300, function () {
            return Server::where('status', 'paused')->count();
        });

        $healthPercentage = $totalServers > 0 ? round(($upServers / $totalServers) * 100, 1) : 0;

        return Stat::make('Server Health', $healthPercentage . '%')
            ->description("$upServers up, $downServers down, $pausedServers paused out of $totalServers servers")
            ->descriptionIcon($healthPercentage >= 90 ? 'heroicon-m-check-circle' : ($healthPercentage >= 70 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-x-circle'))
            ->color($healthPercentage >= 90 ? 'success' : ($healthPercentage >= 70 ? 'warning' : 'danger'))
            ->chart($this->getServerHealthChart());
    }

    private function getActiveConnectionsStat(): Stat
    {
        $activeConnections = Cache::remember('active_connections_count', 300, function () {
            return Server::with('clients')
                ->get()
                ->sum(function ($server) {
                    return $server->clients()->where('status', 'active')->count();
                });
        });

        $trend = $this->getConnectionTrend();

        return Stat::make('Active Connections', number_format($activeConnections))
            ->description($trend['description'])
            ->descriptionIcon($trend['icon'])
            ->color($trend['color'])
            ->chart($trend['chart']);
    }

    private function getServerLoadStat(): Stat
    {
        $averageLoad = Cache::remember('average_server_load', 300, function () {
            $servers = Server::where('status', 'up')->get();
            if ($servers->isEmpty()) {
                return 0;
            }

            $totalLoad = $servers->sum(function ($server) {
                // Use performance_metrics->cpu_load if available, else simulate
                $metrics = $server->performance_metrics;
                if (is_array($metrics) && isset($metrics['cpu_load'])) {
                    return (float) $metrics['cpu_load'];
                } elseif (is_string($metrics)) {
                    $decoded = json_decode($metrics, true);
                    return isset($decoded['cpu_load']) ? (float) $decoded['cpu_load'] : $this->getServerCpuUsage($server);
                }
                return $this->getServerCpuUsage($server);
            });

            return round($totalLoad / $servers->count(), 1);
        });

        return Stat::make('Average CPU Load', $averageLoad . '%')
            ->description($this->getLoadDescription($averageLoad))
            ->descriptionIcon($averageLoad < 70 ? 'heroicon-m-cpu-chip' : 'heroicon-m-fire')
            ->color($averageLoad < 70 ? 'success' : ($averageLoad < 85 ? 'warning' : 'danger'))
            ->chart($this->getLoadChart());
    }

    private function getXUIConnectionsStat(): Stat
    {
        $xuiStatus = Cache::remember('xui_connections_status', 300, function () {
            $servers = Server::where('status', 'up')->get();
            $connected = 0;
            $failed = 0;

            foreach ($servers as $server) {
                try {
                    $xuiService = new XUIService($server);
                    if ($xuiService->testConnection()) {
                        $connected++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning("XUI connection failed for server {$server->id}: " . $e->getMessage());
                }
            }

            return [
                'connected' => $connected,
                'failed' => $failed,
                'total' => $servers->count()
            ];
        });

        $percentage = $xuiStatus['total'] > 0
            ? round(($xuiStatus['connected'] / $xuiStatus['total']) * 100, 1)
            : 0;

        return Stat::make('XUI Connections', $percentage . '%')
            ->description("{$xuiStatus['connected']} of {$xuiStatus['total']} panels connected")
            ->descriptionIcon($percentage >= 90 ? 'heroicon-m-globe-alt' : 'heroicon-m-x-circle')
            ->color($percentage >= 90 ? 'success' : ($percentage >= 70 ? 'warning' : 'danger'));
    }

    private function getTotalTrafficStat(): Stat
    {
        $trafficData = Cache::remember('total_traffic_stats', 300, function () {
            $totalUp = 0;
            $totalDown = 0;

            Server::with('clients')->get()->each(function ($server) use (&$totalUp, &$totalDown) {
                $server->clients->each(function ($client) use (&$totalUp, &$totalDown) {
                    $totalUp += $client->up ?? 0;
                    $totalDown += $client->down ?? 0;
                });
            });

            // Add global_traffic_stats from server if available
            Server::all()->each(function ($server) use (&$totalUp, &$totalDown) {
                $stats = $server->global_traffic_stats;
                if (is_array($stats)) {
                    $totalUp += $stats['up'] ?? 0;
                    $totalDown += $stats['down'] ?? 0;
                } elseif (is_string($stats)) {
                    $decoded = json_decode($stats, true);
                    $totalUp += $decoded['up'] ?? 0;
                    $totalDown += $decoded['down'] ?? 0;
                }
            });

            return [
                'up' => $totalUp,
                'down' => $totalDown,
                'total' => $totalUp + $totalDown
            ];
        });

        return Stat::make('Total Traffic', $this->formatBytes($trafficData['total']))
            ->description("↑ {$this->formatBytes($trafficData['up'])} ↓ {$this->formatBytes($trafficData['down'])}")
            ->descriptionIcon('heroicon-m-signal')
            ->color('info')
            ->chart($this->getTrafficChart());
    }

    private function getServerPerformanceStat(): Stat
    {
        $performanceScore = Cache::remember('server_performance_score', 300, function () {
            $servers = Server::where('status', 'up')->get();
            if ($servers->isEmpty()) {
                return 0;
            }

            $totalScore = $servers->sum(function ($server) {
                // Use performance_metrics->score if available, else calculate
                $metrics = $server->performance_metrics;
                if (is_array($metrics) && isset($metrics['score'])) {
                    return (float) $metrics['score'];
                } elseif (is_string($metrics)) {
                    $decoded = json_decode($metrics, true);
                    return isset($decoded['score']) ? (float) $decoded['score'] : $this->calculatePerformanceScore($server);
                }
                return $this->calculatePerformanceScore($server);
            });

            return round($totalScore / $servers->count(), 1);
        });

        return Stat::make('Performance Score', $performanceScore . '/100')
            ->description($this->getPerformanceDescription($performanceScore))
            ->descriptionIcon($performanceScore >= 80 ? 'heroicon-m-star' : 'heroicon-m-wrench-screwdriver')
            ->color($performanceScore >= 80 ? 'success' : ($performanceScore >= 60 ? 'warning' : 'danger'))
            ->chart($this->getPerformanceChart());
    }

    private function getServerHealthChart(): array
    {
        return Cache::remember('server_health_chart', 300, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $activeCount = Server::where('status', 'active')
                    ->whereDate('updated_at', $date)
                    ->count();
                $data[] = $activeCount;
            }
            return $data;
        });
    }

    private function getConnectionTrend(): array
    {
        $currentHour = Cache::remember('connections_current_hour', 60, function () {
            return Server::with('clients')
                ->get()
                ->sum(function ($server) {
                    return $server->clients()
                        ->where('status', 'active')
                        ->where('server_clients.updated_at', '>=', now()->subHour())
                        ->count();
                });
        });

        $previousHour = Cache::remember('connections_previous_hour', 300, function () {
            return Server::with('clients')
                ->get()
                ->sum(function ($server) {
                    return $server->clients()
                        ->where('status', 'active')
                        ->whereBetween('server_clients.updated_at', [now()->subHours(2), now()->subHour()])
                        ->count();
                });
        });

        $change = $currentHour - $previousHour;
        $changePercent = $previousHour > 0 ? round(($change / $previousHour) * 100, 1) : 0;

        if ($change > 0) {
            return [
                'description' => "↗ {$change} (+{$changePercent}%) from last hour",
                'icon' => 'heroicon-m-arrow-trending-up',
                'color' => 'success',
                'chart' => $this->getHourlyConnectionChart()
            ];
        } elseif ($change < 0) {
            return [
                'description' => "↘ " . abs($change) . " ({$changePercent}%) from last hour",
                'icon' => 'heroicon-m-arrow-trending-down',
                'color' => 'danger',
                'chart' => $this->getHourlyConnectionChart()
            ];
        } else {
            return [
                'description' => "→ No change from last hour",
                'icon' => 'heroicon-m-minus',
                'color' => 'gray',
                'chart' => $this->getHourlyConnectionChart()
            ];
        }
    }

    private function getHourlyConnectionChart(): array
    {
        return Cache::remember('hourly_connection_chart', 300, function () {
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $hour = now()->subHours($i);
                $connections = Server::with('clients')
                    ->get()
                    ->sum(function ($server) use ($hour) {
                        return $server->clients()
                            ->where('status', 'active')
                            ->whereBetween('server_clients.updated_at', [$hour, $hour->copy()->addHour()])
                            ->count();
                    });
                $data[] = $connections;
            }
            return $data;
        });
    }

    private function getServerCpuUsage(Server $server): float
    {
        // Simulate CPU usage - in real implementation, this would fetch from monitoring API
        return rand(10, 90);
    }

    private function getLoadDescription(float $load): string
    {
        if ($load < 50) {
            return 'Optimal performance';
        } elseif ($load < 70) {
            return 'Good performance';
        } elseif ($load < 85) {
            return 'High load - monitor closely';
        } else {
            return 'Critical load - immediate attention needed';
        }
    }

    private function getLoadChart(): array
    {
        return Cache::remember('load_chart', 300, function () {
            $data = [];
            for ($i = 23; $i >= 0; $i--) {
                $data[] = rand(20, 80); // Simulate load data
            }
            return $data;
        });
    }

    private function calculatePerformanceScore(Server $server): float
    {
        // Calculate performance score based on multiple factors
        $score = 100;

        // CPU usage impact
        $cpuUsage = $this->getServerCpuUsage($server);
        if ($cpuUsage > 85) {
            $score -= 30;
        } elseif ($cpuUsage > 70) {
            $score -= 15;
        }

        // Client count impact
        $clientCount = $server->clients()->where('status', 'active')->count();
        $maxClients = 100; // Assume max 100 clients per server
        if ($clientCount > $maxClients * 0.9) {
            $score -= 20;
        } elseif ($clientCount > $maxClients * 0.7) {
            $score -= 10;
        }

        // Uptime impact (simulate)
        $uptime = rand(95, 100);
        if ($uptime < 99) {
            $score -= (99 - $uptime) * 2;
        }

        return max(0, $score);
    }

    private function getPerformanceDescription(float $score): string
    {
        if ($score >= 90) {
            return 'Excellent performance across all servers';
        } elseif ($score >= 80) {
            return 'Good performance with minor optimizations needed';
        } elseif ($score >= 60) {
            return 'Moderate performance - consider optimizations';
        } else {
            return 'Poor performance - immediate action required';
        }
    }

    private function getPerformanceChart(): array
    {
        return Cache::remember('performance_chart', 300, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $data[] = rand(60, 100); // Simulate performance data
            }
            return $data;
        });
    }

    private function getTrafficChart(): array
    {
        return Cache::remember('traffic_chart', 300, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $data[] = rand(50, 200); // Simulate traffic data in GB
            }
            return $data;
        });
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}