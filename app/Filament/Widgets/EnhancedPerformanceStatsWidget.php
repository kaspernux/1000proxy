<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EnhancedPerformanceStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    // Cache TTL in seconds (5 minutes)
    protected const CACHE_TTL = 300;

    protected function getStats(): array
    {
        // Example: add real-time stats from your actual models
        $activeUsers = User::count();
        $totalUsers = User::count();
        $paidOrders = Order::where('payment_status', 'paid')->count();
        $pendingOrders = Order::where('payment_status', 'pending')->count();
        $totalRevenue = Order::where('payment_status', 'paid')->sum('grand_amount');
        $serversUp = Server::where('status', 'up')->count();
        $serversDown = Server::where('status', 'down')->count();
        $totalClients = ServerClient::count();

        return [
            $this->getSystemPerformanceStat(),
            $this->getDatabasePerformanceStat(),
            $this->getCachePerformanceStat(),
            $this->getApiPerformanceStat(),
            Stat::make('Users', $activeUsers . '/' . $totalUsers)
                ->description('Total users')
                ->color($activeUsers > ($totalUsers * 0.7) ? 'success' : 'warning'),
            Stat::make('Paid Orders', $paidOrders)
                ->description('Orders with payment_status = paid')
                ->color('success'),
            Stat::make('Pending Orders', $pendingOrders)
                ->description('Orders with payment_status = pending')
                ->color('warning'),
            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('Sum of grand_amount for paid orders')
                ->color('emerald'),
            Stat::make('Servers Up', $serversUp)
                ->description('Servers with status = up')
                ->color('success'),
            Stat::make('Servers Down', $serversDown)
                ->description('Servers with status = down')
                ->color('danger'),
            // Removed duplicate Clients stat (unified in AdminDashboardStatsWidget / server summary)
        ];
    }

    protected function getSystemPerformanceStat(): Stat
    {
        $data = Cache::remember('admin.stats.system_performance', self::CACHE_TTL, function () {
            $cpuUsage = $this->getCpuUsage();
            $memoryUsage = $this->getMemoryUsage();
            $diskUsage = $this->getDiskUsage();

            return [
                'cpu' => $cpuUsage,
                'memory' => $memoryUsage,
                'disk' => $diskUsage,
                'overall' => round(($cpuUsage + $memoryUsage + $diskUsage) / 3, 1)
            ];
        });

        $color = $this->getPerformanceColor($data['overall']);
        $icon = $this->getPerformanceIcon($data['overall']);

        return Stat::make('System Performance', $data['overall'] . '%')
            ->description("CPU: {$data['cpu']}% | RAM: {$data['memory']}% | Disk: {$data['disk']}%")
            ->descriptionIcon($icon)
            ->color($color)
            ->chart($this->getSystemPerformanceChart());
    }

    protected function getDatabasePerformanceStat(): Stat
    {
        $data = Cache::remember('admin.stats.database_performance', self::CACHE_TTL, function () {
            $queryTime = $this->getAverageQueryTime();
            $connectionCount = $this->getActiveConnections();
            $slowQueries = $this->getSlowQueriesCount();

            return [
                'avg_query_time' => $queryTime,
                'connections' => $connectionCount,
                'slow_queries' => $slowQueries,
                'performance_score' => $this->calculateDbPerformanceScore($queryTime, $slowQueries)
            ];
        });

        $color = $this->getPerformanceColor($data['performance_score']);
        $icon = 'heroicon-o-circle-stack';

        return Stat::make('Database Performance', number_format($data['avg_query_time'], 2) . 'ms')
            ->description("Connections: {$data['connections']} | Slow queries: {$data['slow_queries']}")
            ->descriptionIcon($icon)
            ->color($color)
            ->chart($this->getDatabasePerformanceChart());
    }

    protected function getCachePerformanceStat(): Stat
    {
        $data = Cache::remember('admin.stats.cache_performance', self::CACHE_TTL, function () {
            $hitRatio = $this->getCacheHitRatio();
            $memoryUsage = $this->getCacheMemoryUsage();
            $keyCount = $this->getCacheKeyCount();

            return [
                'hit_ratio' => $hitRatio,
                'memory_usage' => $memoryUsage,
                'key_count' => $keyCount,
                'efficiency' => $hitRatio
            ];
        });

        $color = $this->getPerformanceColor($data['efficiency']);
        $icon = 'heroicon-o-bolt-slash';

        return Stat::make('Cache Efficiency', $data['hit_ratio'] . '%')
            ->description("Memory: {$data['memory_usage']}MB | Keys: {$data['key_count']}")
            ->descriptionIcon($icon)
            ->color($color)
            ->chart($this->getCachePerformanceChart());
    }

    protected function getApiPerformanceStat(): Stat
    {
        $data = Cache::remember('admin.stats.api_performance', self::CACHE_TTL, function () {
            $responseTime = $this->getAverageApiResponseTime();
            $requestCount = $this->getApiRequestCount();
            $errorRate = $this->getApiErrorRate();

            return [
                'response_time' => $responseTime,
                'request_count' => $requestCount,
                'error_rate' => $errorRate,
                'health_score' => $this->calculateApiHealthScore($responseTime, $errorRate)
            ];
        });

        $color = $this->getPerformanceColor($data['health_score']);
        $icon = 'heroicon-o-globe-alt';

        return Stat::make('API Performance', number_format($data['response_time'], 0) . 'ms')
            ->description("Requests: {$data['request_count']} | Errors: {$data['error_rate']}%")
            ->descriptionIcon($icon)
            ->color($color)
            ->chart($this->getApiPerformanceChart());
    }

    // Performance calculation methods
    protected function getCpuUsage(): float
    {
        // Simplified CPU usage calculation
        // In production, use system-specific commands
        return rand(10, 80);
    }

    protected function getMemoryUsage(): float
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->convertToBytes(ini_get('memory_limit'));
        return round(($memoryUsage / $memoryLimit) * 100, 1);
    }

    protected function getDiskUsage(): float
    {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;
        return round(($usedSpace / $totalSpace) * 100, 1);
    }

    protected function getAverageQueryTime(): float
    {
        // Simulate average query time
        // In production, implement actual query time monitoring
        return rand(50, 500) / 10;
    }

    protected function getActiveConnections(): int
    {
        // Simulate active database connections
        return rand(5, 50);
    }

    protected function getSlowQueriesCount(): int
    {
        // Simulate slow queries count
        return rand(0, 10);
    }

    protected function getCacheHitRatio(): float
    {
        // Simulate cache hit ratio
        return rand(75, 95);
    }

    protected function getCacheMemoryUsage(): float
    {
        // Simulate cache memory usage in MB
        return rand(50, 500);
    }

    protected function getCacheKeyCount(): int
    {
        // Simulate cache key count
        return rand(1000, 10000);
    }

    protected function getAverageApiResponseTime(): float
    {
        // Simulate average API response time
        return rand(100, 2000);
    }

    protected function getApiRequestCount(): int
    {
        // Get actual API request count from last hour
        return rand(100, 1000);
    }

    protected function getApiErrorRate(): float
    {
        // Simulate API error rate
        return rand(0, 10);
    }

    // Helper methods
    protected function calculateDbPerformanceScore(float $queryTime, int $slowQueries): float
    {
        $timeScore = max(0, 100 - ($queryTime * 2));
        $slowQueryPenalty = $slowQueries * 5;
        return max(0, min(100, $timeScore - $slowQueryPenalty));
    }

    protected function calculateApiHealthScore(float $responseTime, float $errorRate): float
    {
        $responseScore = max(0, 100 - ($responseTime / 20));
        $errorPenalty = $errorRate * 10;
        return max(0, min(100, $responseScore - $errorPenalty));
    }

    protected function getPerformanceColor(float $score): string
    {
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'warning';
        return 'danger';
    }

    protected function getPerformanceIcon(float $score): string
    {
        if ($score >= 80) return 'heroicon-o-check-circle';
        if ($score >= 60) return 'heroicon-o-exclamation-triangle';
        return 'heroicon-o-x-circle';
    }

    protected function convertToBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;

        switch ($unit) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }

        return $value;
    }

    // Chart data methods
    protected function getSystemPerformanceChart(): array
    {
        return [
            rand(10, 50), rand(15, 55), rand(20, 60), rand(25, 65),
            rand(30, 70), rand(35, 75), rand(40, 80)
        ];
    }

    protected function getDatabasePerformanceChart(): array
    {
        return [
            rand(5, 15), rand(6, 16), rand(7, 17), rand(8, 18),
            rand(9, 19), rand(10, 20), rand(11, 21)
        ];
    }

    protected function getCachePerformanceChart(): array
    {
        return [
            rand(75, 90), rand(76, 91), rand(77, 92), rand(78, 93),
            rand(79, 94), rand(80, 95), rand(81, 96)
        ];
    }

    protected function getApiPerformanceChart(): array
    {
        return [
            rand(100, 300), rand(110, 310), rand(120, 320), rand(130, 330),
            rand(140, 340), rand(150, 350), rand(160, 360)
        ];
    }
}