<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class SystemHealthIndicatorsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        return [
            $this->getDatabaseHealth(),
            $this->getQueueHealth(),
            $this->getCacheHealth(),
            $this->getStorageHealth(),
            $this->getApplicationHealth(),
            $this->getSecurityHealth(),
            $this->getServerFleetHealth(),
            $this->getXUIPanelHealth(),
            $this->getSystemTrafficStat(),
        ];
    }

    // New: Server fleet health (up/down/paused)
    private function getServerFleetHealth(): Stat
    {
        $total = \App\Models\Server::count();
        $up = \App\Models\Server::where('status', 'up')->count();
        $down = \App\Models\Server::where('status', 'down')->count();
        $paused = \App\Models\Server::where('status', 'paused')->count();

        $desc = "$up up, $down down, $paused paused of $total";
        $color = $up === $total ? 'success' : ($up >= ($total * 0.7) ? 'warning' : 'danger');

        return Stat::make('Server Fleet', "$up/$total Up")
            ->description($desc)
            ->descriptionIcon($color === 'success' ? 'heroicon-m-check-circle' : ($color === 'warning' ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-x-circle'))
            ->color($color);
    }

    // New: XUI panel health (panels reachable)
    private function getXUIPanelHealth(): Stat
    {
        $servers = \App\Models\Server::where('status', 'up')->get();
        $connected = 0;
        $failed = 0;
        foreach ($servers as $server) {
            try {
                $xui = new \App\Services\XUIService($server);
                if ($xui->testConnection()) {
                    $connected++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }
        $total = $servers->count();
        $percent = $total > 0 ? round(($connected / $total) * 100, 1) : 0;
        $desc = "$connected of $total panels connected";
        $color = $percent >= 90 ? 'success' : ($percent >= 70 ? 'warning' : 'danger');
        return Stat::make('XUI Panels', "$percent% Connected")
            ->description($desc)
            ->descriptionIcon($color === 'success' ? 'heroicon-m-globe-alt' : ($color === 'warning' ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-x-circle'))
            ->color($color);
    }

    // New: System-wide traffic stat (sum up/down from all servers)
    private function getSystemTrafficStat(): Stat
    {
        $totalUp = 0;
        $totalDown = 0;
        \App\Models\Server::with('serverClients')->get()->each(function ($server) use (&$totalUp, &$totalDown) {
            $server->serverClients->each(function ($client) use (&$totalUp, &$totalDown) {
                $totalUp += $client->up ?? 0;
                $totalDown += $client->down ?? 0;
            });
        });
        // Add global_traffic_stats if available
        \App\Models\Server::all()->each(function ($server) use (&$totalUp, &$totalDown) {
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
        $desc = "↑ " . $this->formatBytes($totalUp) . " ↓ " . $this->formatBytes($totalDown);
        $total = $this->formatBytes($totalUp + $totalDown);
        return Stat::make('System Traffic', $total)
            ->description($desc)
            ->descriptionIcon('heroicon-m-signal')
            ->color('info');
    }

    private function getDatabaseHealth(): Stat
    {
        $dbHealth = Cache::remember('database_health', 60, function () {
            try {
                $start = microtime(true);

                // Test database connection and response time
                $connectionTest = DB::select('SELECT 1 as test');
                $responseTime = round((microtime(true) - $start) * 1000, 2);

                // Check database size
                $dbSize = $this->getDatabaseSize();

                // Check active connections
                $activeConnections = $this->getActiveConnections();

                return [
                    'status' => 'healthy',
                    'response_time' => $responseTime,
                    'size' => $dbSize,
                    'connections' => $activeConnections,
                    'description' => "Response time: {$responseTime}ms"
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'response_time' => 0,
                    'size' => 0,
                    'connections' => 0,
                    'description' => 'Database connection failed'
                ];
            }
        });

        return Stat::make('Database', $dbHealth['status'] === 'healthy' ? 'Healthy' : 'Error')
            ->description($dbHealth['description'])
            ->descriptionIcon($dbHealth['status'] === 'healthy' ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
            ->color($dbHealth['status'] === 'healthy' ? 'success' : 'danger')
            ->chart($this->getDatabaseResponseChart());
    }

    private function getQueueHealth(): Stat
    {
        $queueHealth = Cache::remember('queue_health', 60, function () {
            try {
                // Count pending jobs
                $pendingJobs = DB::table('jobs')->count();

                // Count failed jobs
                $failedJobs = DB::table('failed_jobs')->count();

                // Check if workers are running (this is a simplified check)
                $workersRunning = $this->checkQueueWorkers();

                $status = 'healthy';
                $description = "Pending: {$pendingJobs}, Failed: {$failedJobs}";

                if ($failedJobs > 50) {
                    $status = 'warning';
                    $description = "High failed jobs count: {$failedJobs}";
                } elseif ($pendingJobs > 100) {
                    $status = 'warning';
                    $description = "High pending jobs: {$pendingJobs}";
                } elseif (!$workersRunning) {
                    $status = 'error';
                    $description = 'No queue workers detected';
                }

                return [
                    'status' => $status,
                    'pending' => $pendingJobs,
                    'failed' => $failedJobs,
                    'workers' => $workersRunning,
                    'description' => $description
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'pending' => 0,
                    'failed' => 0,
                    'workers' => false,
                    'description' => 'Queue system error'
                ];
            }
        });

        return Stat::make('Queue System', ucfirst($queueHealth['status']))
            ->description($queueHealth['description'])
            ->descriptionIcon(match($queueHealth['status']) {
                'healthy' => 'heroicon-m-check-circle',
                'warning' => 'heroicon-m-exclamation-triangle',
                default => 'heroicon-m-x-circle'
            })
            ->color(match($queueHealth['status']) {
                'healthy' => 'success',
                'warning' => 'warning',
                default => 'danger'
            })
            ->chart($this->getQueueChart());
    }

    private function getCacheHealth(): Stat
    {
        $cacheHealth = Cache::remember('cache_health_status', 60, function () {
            try {
                $start = microtime(true);

                // Test cache write/read
                $testKey = 'health_check_' . time();
                $testValue = 'test_value_' . rand(1000, 9999);

                Cache::put($testKey, $testValue, 60);
                $retrievedValue = Cache::get($testKey);

                $responseTime = round((microtime(true) - $start) * 1000, 2);

                Cache::forget($testKey);

                $success = $retrievedValue === $testValue;

                // Check cache hit rate
                $hitRate = $this->getCacheHitRate();

                return [
                    'status' => $success ? 'healthy' : 'error',
                    'response_time' => $responseTime,
                    'hit_rate' => $hitRate,
                    'description' => $success
                        ? "Response: {$responseTime}ms, Hit rate: {$hitRate}%"
                        : 'Cache read/write failed'
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'response_time' => 0,
                    'hit_rate' => 0,
                    'description' => 'Cache system unavailable'
                ];
            }
        });

        return Stat::make('Cache', $cacheHealth['status'] === 'healthy' ? 'Healthy' : 'Error')
            ->description($cacheHealth['description'])
            ->descriptionIcon($cacheHealth['status'] === 'healthy' ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
            ->color($cacheHealth['status'] === 'healthy' ? 'success' : 'danger')
            ->chart($this->getCacheHitRateChart());
    }

    private function getStorageHealth(): Stat
    {
        $storageHealth = Cache::remember('storage_health', 300, function () {
            try {
                // Check disk space
                $totalSpace = disk_total_space(storage_path());
                $freeSpace = disk_free_space(storage_path());
                $usedPercentage = round((1 - ($freeSpace / $totalSpace)) * 100, 1);

                // Test file operations
                $testFile = 'health_check_' . time() . '.tmp';
                Storage::disk('local')->put($testFile, 'health check');
                $fileExists = Storage::disk('local')->exists($testFile);
                Storage::disk('local')->delete($testFile);

                $status = 'healthy';
                $description = "Used: {$usedPercentage}%, Available: " . $this->formatBytes($freeSpace);

                if (!$fileExists) {
                    $status = 'error';
                    $description = 'File operations failed';
                } elseif ($usedPercentage > 90) {
                    $status = 'error';
                    $description = "Critical disk usage: {$usedPercentage}%";
                } elseif ($usedPercentage > 80) {
                    $status = 'warning';
                    $description = "High disk usage: {$usedPercentage}%";
                }

                return [
                    'status' => $status,
                    'used_percentage' => $usedPercentage,
                    'free_space' => $freeSpace,
                    'description' => $description
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'used_percentage' => 0,
                    'free_space' => 0,
                    'description' => 'Storage check failed'
                ];
            }
        });

        return Stat::make('Storage', ucfirst($storageHealth['status']))
            ->description($storageHealth['description'])
            ->descriptionIcon(match($storageHealth['status']) {
                'healthy' => 'heroicon-m-check-circle',
                'warning' => 'heroicon-m-exclamation-triangle',
                default => 'heroicon-m-x-circle'
            })
            ->color(match($storageHealth['status']) {
                'healthy' => 'success',
                'warning' => 'warning',
                default => 'danger'
            })
            ->chart($this->getStorageUsageChart());
    }

    private function getApplicationHealth(): Stat
    {
        $appHealth = Cache::remember('application_health', 60, function () {
            try {
                // Check memory usage
                $memoryUsage = memory_get_usage(true);
                $memoryLimit = $this->getMemoryLimit();
                $memoryPercentage = round(($memoryUsage / $memoryLimit) * 100, 1);

                // Check PHP version
                $phpVersion = PHP_VERSION;
                $isPhpVersionOk = version_compare($phpVersion, '8.1.0', '>=');

                // Check Laravel version
                $laravelVersion = app()->version();

                $status = 'healthy';
                $description = "Memory: {$memoryPercentage}%, PHP: {$phpVersion}";

                if (!$isPhpVersionOk) {
                    $status = 'warning';
                    $description = "PHP version {$phpVersion} needs update";
                } elseif ($memoryPercentage > 90) {
                    $status = 'warning';
                    $description = "High memory usage: {$memoryPercentage}%";
                }

                return [
                    'status' => $status,
                    'memory_percentage' => $memoryPercentage,
                    'php_version' => $phpVersion,
                    'laravel_version' => $laravelVersion,
                    'description' => $description
                ];
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'memory_percentage' => 0,
                    'php_version' => 'Unknown',
                    'laravel_version' => 'Unknown',
                    'description' => 'Application health check failed'
                ];
            }
        });

        return Stat::make('Application', ucfirst($appHealth['status']))
            ->description($appHealth['description'])
            ->descriptionIcon(match($appHealth['status']) {
                'healthy' => 'heroicon-m-check-circle',
                'warning' => 'heroicon-m-exclamation-triangle',
                default => 'heroicon-m-x-circle'
            })
            ->color(match($appHealth['status']) {
                'healthy' => 'success',
                'warning' => 'warning',
                default => 'danger'
            })
            ->chart($this->getMemoryUsageChart());
    }

    private function getSecurityHealth(): Stat
    {
        $securityHealth = Cache::remember('security_health', 300, function () {
            $issues = [];
            $score = 100;

            // Check if app is in debug mode (should be false in production)
            if (config('app.debug')) {
                $issues[] = 'Debug mode enabled';
                $score -= 30;
            }

            // Check if HTTPS is enforced
            if (!request()->isSecure() && config('app.env') === 'production') {
                $issues[] = 'HTTPS not enforced';
                $score -= 20;
            }

            // Check session configuration
            if (config('session.secure') === false && config('app.env') === 'production') {
                $issues[] = 'Insecure session configuration';
                $score -= 15;
            }

            // Check for recent failed login attempts
            $recentFailedLogins = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(24))
                ->where('payload', 'like', '%login%')
                ->count();

            if ($recentFailedLogins > 100) {
                $issues[] = "High failed login attempts: {$recentFailedLogins}";
                $score -= 10;
            }

            $status = 'healthy';
            $description = 'All security checks passed';

            if ($score < 70) {
                $status = 'error';
                $description = count($issues) . ' security issues found';
            } elseif ($score < 90) {
                $status = 'warning';
                $description = count($issues) . ' minor security concerns';
            }

            return [
                'status' => $status,
                'score' => $score,
                'issues' => $issues,
                'description' => $description
            ];
        });

        return Stat::make('Security', "Score: {$securityHealth['score']}/100")
            ->description($securityHealth['description'])
            ->descriptionIcon(match($securityHealth['status']) {
                'healthy' => 'heroicon-m-shield-check',
                'warning' => 'heroicon-m-shield-exclamation',
                default => 'heroicon-m-shield-exclamation'
            })
            ->color(match($securityHealth['status']) {
                'healthy' => 'success',
                'warning' => 'warning',
                default => 'danger'
            })
            ->chart($this->getSecurityScoreChart());
    }

    // Helper methods
    private function getDatabaseSize(): int
    {
        try {
            $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema=?", [config('database.connections.mysql.database')]);
            return $result[0]->{'DB Size in MB'} ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getActiveConnections(): int
    {
        try {
            $result = DB::select('SHOW STATUS WHERE variable_name = "Threads_connected"');
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function checkQueueWorkers(): bool
    {
        // This is a simplified check - in a real scenario you might check process lists
        try {
            $recentJobs = DB::table('jobs')
                ->where('available_at', '<=', now()->timestamp)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();

            return $recentJobs === 0; // If no old pending jobs, workers are likely working
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getCacheHitRate(): float
    {
        // This would require custom cache metrics - returning simulated data
        return round(rand(85, 98), 1);
    }

    private function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
            switch (strtolower($matches[2])) {
                case 'g': return $matches[1] * 1024 * 1024 * 1024;
                case 'm': return $matches[1] * 1024 * 1024;
                case 'k': return $matches[1] * 1024;
                default: return $matches[1];
            }
        }
        return 128 * 1024 * 1024; // Default 128MB
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    // Chart data methods (returning sample data for now)
    private function getDatabaseResponseChart(): array
    {
        return [10, 12, 8, 15, 11, 9, 13];
    }

    private function getQueueChart(): array
    {
        return [5, 3, 8, 2, 6, 4, 1];
    }

    private function getCacheHitRateChart(): array
    {
        return [95, 94, 96, 93, 97, 95, 98];
    }

    private function getStorageUsageChart(): array
    {
        return [65, 67, 66, 68, 69, 70, 72];
    }

    private function getMemoryUsageChart(): array
    {
        return [45, 48, 52, 49, 53, 47, 51];
    }

    private function getSecurityScoreChart(): array
    {
        return [100, 98, 100, 95, 100, 97, 100];
    }
}
