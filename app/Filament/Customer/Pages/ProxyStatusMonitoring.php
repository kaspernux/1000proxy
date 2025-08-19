<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use App\Models\ServerClient;
use App\Models\Order;
use App\Models\Server;
use App\Services\XUIService;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use BackedEnum;

class ProxyStatusMonitoring extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationLabel = 'Proxy Status Monitor';
    protected string $view = 'filament.customer.pages.proxy-status-monitoring';
    protected static ?int $navigationSort = 6;

    protected ?string $pollingInterval = '30s';

    public $proxyStatuses = [];
    public $overallMetrics = [];
    public $alertsAndIssues = [];
    public $performanceHistory = [];
    public $selectedTimeRange = '24h';
    public $autoRefresh = true;
    public $showOfflineOnly = false;
    public $selectedProxy = null;

    public function mount(): void
    {
        $this->loadProxyStatuses();
        $this->calculateOverallMetrics();
        $this->checkAlertsAndIssues();
        $this->loadPerformanceHistory();
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('refresh_all')
                ->label('Refresh All')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action('refreshAllStatuses'),

            PageAction::make('test_all')
                ->label('Test All Connections')
                ->icon('heroicon-o-signal')
                ->color('success')
                ->action('testAllConnections'),

            PageAction::make('export_report')
                ->label('Export Status Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('exportStatusReport'),

            PageAction::make('toggle_auto_refresh')
                ->label($this->autoRefresh ? 'Disable Auto-Refresh' : 'Enable Auto-Refresh')
                ->icon($this->autoRefresh ? 'heroicon-o-pause' : 'heroicon-o-play')
                ->color($this->autoRefresh ? 'warning' : 'success')
                ->action('toggleAutoRefresh'),
        ];
    }

    public function refreshAllStatuses(): void
    {
        $this->loadProxyStatuses(true); // Force refresh
        $this->calculateOverallMetrics();
        $this->checkAlertsAndIssues();

        Notification::make()
            ->title('Status Refreshed')
            ->body('All proxy statuses have been updated')
            ->success()
            ->send();
    }

    public function testAllConnections(): void
    {
        $customer = Auth::guard('customer')->user();
        $proxies = $this->getUserProxies();
        $testedCount = 0;
        $successCount = 0;

        foreach ($proxies as $proxy) {
            $testResult = $this->testProxyConnection($proxy);
            if ($testResult['success']) {
                $successCount++;
            }
            $testedCount++;
        }

        $this->loadProxyStatuses(true); // Refresh after testing
        $this->calculateOverallMetrics();

        Notification::make()
            ->title('Connection Tests Complete')
            ->body("Tested {$testedCount} proxies - {$successCount} successful, " . ($testedCount - $successCount) . " failed")
            ->success()
            ->send();
    }

    public function exportStatusReport(): void
    {
        $customer = Auth::guard('customer')->user();
        $reportData = [
            'report_date' => now()->toISOString(),
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'time_range' => $this->selectedTimeRange,
            'overall_metrics' => $this->overallMetrics,
            'proxy_statuses' => $this->proxyStatuses,
            'alerts_and_issues' => $this->alertsAndIssues,
            'performance_history' => $this->performanceHistory,
        ];

        $filename = "proxy_status_report_" . now()->format('Y-m-d_H-i-s') . ".json";
        \Storage::disk('public')->put("reports/{$filename}", json_encode($reportData, JSON_PRETTY_PRINT));

        Notification::make()
            ->title('Report Generated')
            ->body("Status report exported: {$filename}")
            ->actions([
                \Filament\Notifications\Actions\Action::make('download')
                    ->button()
                    ->url(\Storage::disk('public')->url("reports/{$filename}"))
                    ->openUrlInNewTab(),
            ])
            ->success()
            ->persistent()
            ->send();
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;

        Notification::make()
            ->title($this->autoRefresh ? 'Auto-Refresh Enabled' : 'Auto-Refresh Disabled')
            ->body($this->autoRefresh ? 'Status will update automatically every 30 seconds' : 'Manual refresh required')
            ->info()
            ->send();
    }

    public function testProxyConnection(array $proxy): array
    {
        try {
            $serverClient = ServerClient::find($proxy['id']);
            if (!$serverClient || !$serverClient->server) {
                return [
                    'success' => false,
                    'latency' => 0,
                    'error' => 'Proxy configuration not found'
                ];
            }

            // Use XUIService to test connection
            $xuiService = new XUIService($serverClient->server);
            $connectionTest = $xuiService->testConnection();

            if ($connectionTest) {
                // Simulate latency test
                $latency = $this->measureLatency($serverClient->server);

                return [
                    'success' => true,
                    'latency' => $latency,
                    'error' => null
                ];
            } else {
                return [
                    'success' => false,
                    'latency' => 0,
                    'error' => 'Connection failed - server unreachable'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'latency' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    public function changeTimeRange(string $range): void
    {
        $this->selectedTimeRange = $range;
        $this->loadPerformanceHistory();
        $this->calculateOverallMetrics();

        Notification::make()
            ->title('Time Range Updated')
            ->body("Showing data for: {$range}")
            ->info()
            ->send();
    }

    public function toggleOfflineFilter(): void
    {
        $this->showOfflineOnly = !$this->showOfflineOnly;
        $this->loadProxyStatuses();
    }

    public function selectProxy(?int $proxyId): void
    {
        $this->selectedProxy = $proxyId;

        if ($proxyId) {
            $proxy = collect($this->proxyStatuses)->firstWhere('id', $proxyId);
            Notification::make()
                ->title('Proxy Selected')
                ->body("Selected: {$proxy['name']} ({$proxy['location']})")
                ->info()
                ->send();
        }
    }

    protected function loadProxyStatuses(bool $forceRefresh = false): void
    {
        $customer = Auth::guard('customer')->user();
        $cacheKey = "proxy_statuses_{$customer->id}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $this->proxyStatuses = Cache::remember($cacheKey, 300, function () use ($customer) {
            return ServerClient::whereHas('order', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })
            ->with(['server.brand', 'server.category', 'order'])
            ->where('status', 'active')
            ->get()
            ->map(function ($client) {
                $status = $this->determineProxyStatus($client);
                return [
                    'id' => $client->id,
                    'name' => $client->server->name ?? "Proxy {$client->id}",
                    'location' => $client->server->location ?? 'Unknown',
                    'brand' => $client->server->brand->name ?? 'Generic',
                    'protocol' => $client->inbound_name ?? 'vless',
                    'status' => $status['status'],
                    'status_text' => $status['text'],
                    'status_color' => $status['color'],
                    'latency' => $status['latency'],
                    'uptime' => $status['uptime'],
                    'last_check' => now(),
                    'data_usage' => [
                        'upload' => $client->up ?? 0,
                        'download' => $client->down ?? 0,
                        'total' => ($client->up ?? 0) + ($client->down ?? 0)
                    ],
                    'connection_details' => [
                        'ip' => $client->server->ip,
                        'port' => $client->server->api_port,
                        'domain' => $client->server->domain,
                        'uuid' => $client->uuid
                    ],
                    'order_info' => [
                        'order_id' => $client->order->id ?? null,
                        'expires_at' => $client->order->expires_at ?? null,
                        'days_remaining' => $client->order && $client->order->expires_at
                            ? Carbon::parse($client->order->expires_at)->diffInDays(now(), false)
                            : null
                    ]
                ];
            })
            ->when($this->showOfflineOnly, function ($collection) {
                return $collection->where('status', 'offline');
            })
            ->sortBy('name')
            ->values()
            ->all();
        });
    }

    protected function calculateOverallMetrics(): void
    {
        $proxies = collect($this->proxyStatuses);
        $totalProxies = $proxies->count();

        if ($totalProxies === 0) {
            $this->overallMetrics = [
                'total_proxies' => 0,
                'online_proxies' => 0,
                'offline_proxies' => 0,
                'uptime_percentage' => 0,
                'average_latency' => 0,
                'total_data_usage' => 0,
                'health_score' => 0
            ];
            return;
        }

        $onlineProxies = $proxies->where('status', 'online')->count();
        $offlineProxies = $proxies->where('status', 'offline')->count();
        $uptimePercentage = round(($onlineProxies / $totalProxies) * 100, 1);
        $averageLatency = round($proxies->where('status', 'online')->avg('latency'), 1);
        $totalDataUsage = $proxies->sum('data_usage.total');

        // Calculate health score based on multiple factors
        $healthScore = $this->calculateHealthScore($proxies);

        $this->overallMetrics = [
            'total_proxies' => $totalProxies,
            'online_proxies' => $onlineProxies,
            'offline_proxies' => $offlineProxies,
            'uptime_percentage' => $uptimePercentage,
            'average_latency' => $averageLatency,
            'total_data_usage' => $this->formatBytes($totalDataUsage),
            'health_score' => $healthScore,
            'performance_trend' => $this->calculatePerformanceTrend(),
            'last_updated' => now()->format('Y-m-d H:i:s')
        ];
    }

    protected function checkAlertsAndIssues(): void
    {
        $proxies = collect($this->proxyStatuses);
        $alerts = [];

        // Check for offline proxies
        $offlineProxies = $proxies->where('status', 'offline');
        if ($offlineProxies->count() > 0) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Offline Proxies Detected',
                'message' => "Found {$offlineProxies->count()} offline " .
                           str('proxy')->plural($offlineProxies->count()),
                'severity' => 'high',
                'proxies' => $offlineProxies->pluck('name')->toArray(),
                'action' => 'test_connections'
            ];
        }

        // Check for high latency
        $highLatencyProxies = $proxies->where('status', 'online')->where('latency', '>', 200);
        if ($highLatencyProxies->count() > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'High Latency Warning',
                'message' => "Found {$highLatencyProxies->count()} " .
                           str('proxy')->plural($highLatencyProxies->count()) . " with high latency (>200ms)",
                'severity' => 'medium',
                'proxies' => $highLatencyProxies->map(function ($proxy) {
                    return "{$proxy['name']} ({$proxy['latency']}ms)";
                })->toArray(),
                'action' => 'check_network'
            ];
        }

        // Check for expiring orders
        $expiringProxies = $proxies->filter(function ($proxy) {
            $daysRemaining = $proxy['order_info']['days_remaining'] ?? null;
            return $daysRemaining !== null && $daysRemaining <= 7 && $daysRemaining > 0;
        });

        if ($expiringProxies->count() > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Proxies Expiring Soon',
                'message' => "Found {$expiringProxies->count()} " .
                           str('proxy')->plural($expiringProxies->count()) . " expiring within 7 days",
                'severity' => 'low',
                'proxies' => $expiringProxies->map(function ($proxy) {
                    $days = $proxy['order_info']['days_remaining'];
                    return "{$proxy['name']} ({$days} " . str('day')->plural($days) . " remaining)";
                })->toArray(),
                'action' => 'renew_subscription'
            ];
        }

        // Check for expired orders
        $expiredProxies = $proxies->filter(function ($proxy) {
            $daysRemaining = $proxy['order_info']['days_remaining'] ?? null;
            return $daysRemaining !== null && $daysRemaining <= 0;
        });

        if ($expiredProxies->count() > 0) {
            $alerts[] = [
                'type' => 'error',
                'title' => 'Expired Proxies',
                'message' => "Found {$expiredProxies->count()} expired " .
                           str('proxy')->plural($expiredProxies->count()),
                'severity' => 'high',
                'proxies' => $expiredProxies->pluck('name')->toArray(),
                'action' => 'renew_immediately'
            ];
        }

        $this->alertsAndIssues = $alerts;
    }

    protected function loadPerformanceHistory(): void
    {
        $customer = Auth::guard('customer')->user();
        $cacheKey = "performance_history_{$customer->id}_{$this->selectedTimeRange}";

        $this->performanceHistory = Cache::remember($cacheKey, 1800, function () {
            $timeRange = $this->getTimeRangeData();
            $history = [];

            for ($i = 0; $i < $timeRange['points']; $i++) {
                $timestamp = $timeRange['start']->copy()->addSeconds($i * $timeRange['interval']);

                // Simulate historical data - in real implementation, this would come from stored metrics
                $history[] = [
                    'timestamp' => $timestamp->toISOString(),
                    'formatted_time' => $timestamp->format($timeRange['format']),
                    'uptime_percentage' => rand(85, 100),
                    'average_latency' => rand(50, 150),
                    'active_connections' => rand(1, 10),
                    'data_transfer' => rand(100, 1000) * 1024 * 1024, // Random MB
                ];
            }

            return $history;
        });
    }

    protected function determineProxyStatus(ServerClient $client): array
    {
        try {
            // Check if server exists and is accessible
            if (!$client->server) {
                return [
                    'status' => 'error',
                    'text' => 'Server configuration missing',
                    'color' => 'danger',
                    'latency' => 0,
                    'uptime' => 0
                ];
            }

            // Test XUI connection
            $xuiService = new XUIService($client->server);
            $isOnline = $xuiService->testConnection();

            if ($isOnline) {
                $latency = $this->measureLatency($client->server);
                $uptime = $this->calculateUptime($client);

                return [
                    'status' => 'online',
                    'text' => 'Connected',
                    'color' => 'success',
                    'latency' => $latency,
                    'uptime' => $uptime
                ];
            } else {
                return [
                    'status' => 'offline',
                    'text' => 'Connection failed',
                    'color' => 'danger',
                    'latency' => 0,
                    'uptime' => $this->calculateUptime($client)
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'text' => 'Status check failed',
                'color' => 'warning',
                'latency' => 0,
                'uptime' => 0
            ];
        }
    }

    protected function measureLatency(Server $server): int
    {
        // Simulate latency measurement - in real implementation, this would ping the server
        return rand(30, 200);
    }

    protected function calculateUptime(ServerClient $client): float
    {
        // Simulate uptime calculation - in real implementation, this would be based on historical data
        return round(rand(95, 100), 1);
    }

    protected function calculateHealthScore(Collection $proxies): int
    {
        if ($proxies->isEmpty()) {
            return 0;
        }

        $totalProxies = $proxies->count();
        $onlineProxies = $proxies->where('status', 'online')->count();
        $averageLatency = $proxies->where('status', 'online')->avg('latency') ?: 0;
        $averageUptime = $proxies->avg('uptime') ?: 0;

        // Calculate score based on multiple factors
        $uptimeScore = ($onlineProxies / $totalProxies) * 40; // 40% weight
        $latencyScore = max(0, (200 - $averageLatency) / 200) * 30; // 30% weight
        $reliabilityScore = ($averageUptime / 100) * 30; // 30% weight

        return round($uptimeScore + $latencyScore + $reliabilityScore);
    }

    protected function calculatePerformanceTrend(): array
    {
        // Simulate trend calculation - in real implementation, this would compare historical data
        return [
            'direction' => 'stable', // up, down, stable
            'percentage_change' => 0,
            'description' => 'Performance stable over the selected period'
        ];
    }

    protected function getTimeRangeData(): array
    {
        return match ($this->selectedTimeRange) {
            '1h' => [
                'start' => now()->subHour(),
                'end' => now(),
                'points' => 12,
                'interval' => 300, // 5 minutes
                'format' => 'H:i'
            ],
            '24h' => [
                'start' => now()->subDay(),
                'end' => now(),
                'points' => 24,
                'interval' => 3600, // 1 hour
                'format' => 'H:00'
            ],
            '7d' => [
                'start' => now()->subWeek(),
                'end' => now(),
                'points' => 7,
                'interval' => 86400, // 1 day
                'format' => 'M j'
            ],
            '30d' => [
                'start' => now()->subMonth(),
                'end' => now(),
                'points' => 30,
                'interval' => 86400, // 1 day
                'format' => 'M j'
            ],
            default => [
                'start' => now()->subDay(),
                'end' => now(),
                'points' => 24,
                'interval' => 3600,
                'format' => 'H:00'
            ]
        };
    }

    protected function getUserProxies(): Collection
    {
        $customer = Auth::guard('customer')->user();

        return ServerClient::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })
        ->with(['server'])
        ->where('status', 'active')
        ->get();
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function getFilteredProxies(): array
    {
        if ($this->showOfflineOnly) {
            return array_filter($this->proxyStatuses, function ($proxy) {
                return $proxy['status'] === 'offline';
            });
        }

        return $this->proxyStatuses;
    }

    public function getTimeRangeOptions(): array
    {
        return [
            '1h' => 'Last Hour',
            '24h' => 'Last 24 Hours',
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days'
        ];
    }
}
