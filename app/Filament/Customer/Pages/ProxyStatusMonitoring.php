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
use App\Services\CacheService;
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
    public $includePending = false;
    public $selectedProxy = null;
    public $searchTerm = '';
    public $protocolFilter = 'all';
    public $selectedQrImage = null;

    public function mount(): void
    {
        $this->loadProxyStatuses();
        $this->calculateOverallMetrics();
        $this->checkAlertsAndIssues();
        $this->loadPerformanceHistory();
    }

    public function getPollingInterval(): ?string
    {
        return $this->autoRefresh ? $this->pollingInterval : null;
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

            PageAction::make('toggle_pending')
                ->label($this->includePending ? 'Hide Pending Purchases' : 'Show Pending Purchases')
                ->icon($this->includePending ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color($this->includePending ? 'warning' : 'gray')
                ->action('togglePending'),
        ];
    }

    public function togglePending(): void
    {
        $this->includePending = !$this->includePending;
        $this->loadProxyStatuses(true);
        $this->calculateOverallMetrics();
        $this->checkAlertsAndIssues();
    }

    public function refreshAllStatuses(): void
    {
    $customer = Auth::guard('customer')->user();
    // Invalidate cached user clients to ensure inbound/plan are reloaded
    (new CacheService())->invalidateUserCaches((int) $customer->id);
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

        $url = \Storage::disk('public')->url("reports/{$filename}");
        $this->js("window.open('{$url}', '_blank')");

        Notification::make()
            ->title('Report Generated')
            ->body("Status report exported: {$filename}. Fallback link: {$url}")
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

    public function copyLink(string $id): void
    {
        $proxy = collect($this->proxyStatuses)->firstWhere('id', $id);
        $link = $proxy['links']['subscription'] ?? null;
        if (!$link) {
            Notification::make()->title('No subscription link available')->info()->send();
            return;
        }
        $this->js("navigator.clipboard.writeText('" . addslashes($link) . "')");
        Notification::make()->title('Subscription link copied')->success()->send();
    }

    public function openQr(string $id): void
    {
        $proxy = collect($this->proxyStatuses)->firstWhere('id', $id);
        $qr = $proxy['links']['qr'] ?? null;
        if (!$qr) {
            Notification::make()->title('No QR code available')->info()->send();
            return;
        }
        // Show QR in an in-page modal for better browser compatibility
        $this->selectedQrImage = $qr;
    }

    public function closeQr(): void
    {
        $this->selectedQrImage = null;
    }

    protected function loadProxyStatuses(bool $forceRefresh = false): void
    {
        $customer = Auth::guard('customer')->user();
        $cacheKey = "proxy_statuses_{$customer->id}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
            // Also invalidate user client cache so relationships (plan/inbound) re-hydrate
            (new CacheService())->invalidateUserCaches((int) $customer->id);
        }

        $this->proxyStatuses = Cache::remember($cacheKey, 300, function () use ($customer) {
            $clients = (new CacheService())->getUserServerClients((int) $customer->id);

            $clientRows = $clients->map(function (ServerClient $client) {
                // Ensure we have a Server model even when server_id isn't set by deriving from inbound
                $server = $client->server ?: optional($client->inbound)->server;
                $client->setRelation('server', $server);
                $status = $this->determineProxyStatus($client);

                // Prefer real-time fields if available
                $latency = is_numeric(optional($client->server)->response_time_ms ?? null)
                    ? (int) $client->server->response_time_ms
                    : ($status['latency'] ?? ($server ? $this->measureLatency($server) : 0));

                $uptime = is_numeric(optional($client->server)->uptime_percentage ?? null)
                    ? (float) $client->server->uptime_percentage
                    : ($status['uptime'] ?? $this->calculateUptime($client));

                // Usage: attempt live fetch via XUI (cached), else prefer local computed
                $liveTraffic = $this->retrieveClientTraffic($client, false);
                $usedMb = $client->bandwidth_used_mb; // accessor
                $usedBytes = (int) (($client->remote_up ?? 0) + ($client->remote_down ?? 0));
                if (is_array($liveTraffic) && isset($liveTraffic['total']) && $liveTraffic['total'] > 0) {
                    $usedBytes = (int) $liveTraffic['total'];
                    $usedMb = round($usedBytes / 1048576, 2);
                } elseif ($usedBytes === 0 && $usedMb > 0) {
                    $usedBytes = (int) round($usedMb * 1048576);
                }

                // Determine limit (MB) from client or plan
                $limitMb = null;
                if (is_numeric($client->traffic_limit_mb ?? null)) {
                    $limitMb = (float) $client->traffic_limit_mb;
                } else {
                    $planForLimit = $client->plan;
                    if (!$planForLimit && $client->order && $client->order->relationLoaded('orderItems')) {
                        $planForLimit = optional($client->order->orderItems->first())->serverPlan;
                    }
                    if ($planForLimit && is_numeric($planForLimit->data_limit_gb ?? null)) {
                        $limitMb = ((float) $planForLimit->data_limit_gb) * 1024.0;
                    }
                }
                $usagePct = null;
                if ($limitMb && $limitMb > 0) {
                    $usagePct = round(min(100, ($usedMb / $limitMb) * 100), 1);
                } elseif (is_numeric($client->traffic_percentage_used ?? null)) {
                    $usagePct = (float) $client->traffic_percentage_used;
                }

                // Resolve plan details with fallback from order items if direct relation is missing
                $planModel = $client->plan;
                if (!$planModel && $client->order && $client->order->relationLoaded('orderItems')) {
                    $planModel = optional($client->order->orderItems->first())->serverPlan;
                }

                return [
                    'id' => $client->id,
                    'name' => optional($client->server)->name ?? ("Proxy {$client->id}"),
                    'location' => optional($client->server)->location ?? optional($client->server)->country ?? 'Unknown',
                    'brand' => optional(optional($client->server)->brand)->name ?? 'Generic',
                    'protocol' => $client->network_type ?? 'vless',
                    'links' => [
                        'subscription' => $client->subscription_link,
                        'qr' => $client->qr_code,
                    ],
                    'plan' => [
                        'id' => $planModel->id ?? null,
                        'name' => $planModel->name ?? null,
                        'days' => $planModel->days ?? null,
                        'data_limit_gb' => $planModel->data_limit_gb ?? null,
                        'protocol' => $planModel->protocol ?? null,
                    ],
                    'inbound' => [
                        'id' => $client->server_inbound_id ?? optional($client->inbound)->id,
                        'protocol' => optional($client->inbound)->protocol,
                        'port' => optional($client->inbound)->port,
                    ],
                    'client' => [
                        'email' => $client->email,
                        'enable' => (bool) ($client->enable ?? true),
                        'limit_ip' => $client->limit_ip ?? 0,
                        'uuid' => $client->id,
                    ],
                    'status' => $status['status'],
                    'status_text' => $status['text'],
                    'status_color' => $status['color'],
                    'latency' => $latency,
                    'uptime' => $uptime,
                    'last_check' => now(),
                    'last_connection_at' => $client->last_connection_at,
                    'data_usage' => [
                        'upload' => (int) ($client->remote_up ?? 0),
                        'download' => (int) ($client->remote_down ?? 0),
                        'total_bytes' => $usedBytes,
                        'total' => $this->formatBytes($usedBytes),
                        'used_mb' => $usedMb,
                        'limit_mb' => $limitMb,
                        'usage_percent' => $usagePct,
                    ],
                    'connection_details' => [
                        'ip' => optional($client->server)->ip ?? null,
                        'port' => optional($client->server)->port ?? optional($client->server)->panel_port ?? null,
                        'host' => optional($client->server)->host ?? null,
                        'uuid' => $client->id,
                    ],
                    'order_info' => [
                        'order_id' => optional($client->order)->id,
                        'expires_at' => optional($client->order)->expires_at,
                        // Positive when in the future, negative when past
                        'days_remaining' => ($client->order && $client->order->expires_at)
                            ? now()->diffInDays(Carbon::parse($client->order->expires_at), false)
                            : null,
                    ],
                ];
            });

            // Also include purchased plans (order items) even if a client record is missing
            $orderItems = $this->includePending ? Order::query()
                ->where('customer_id', $customer->id)
                ->whereHas('items')
                ->with(['items.serverPlan.server'])
                ->get()
                ->flatMap(function ($order) { return $order->items; }) : collect();

            $orderRows = $orderItems->map(function ($item) {
                $plan = $item->serverPlan;
                $server = optional($plan)->server;
                $name = $server->name ?? ($plan->name ?? 'Purchased Plan');
                return [
                    'id' => 'order_item_' . $item->id,
                    'name' => $name,
                    'location' => optional($server)->location ?? optional($server)->country ?? 'Unknown',
                    'brand' => optional(optional($server)->brand)->name ?? 'Generic',
                    'protocol' => $plan->protocol ?? 'vless',
                    'plan' => [
                        'id' => $plan->id ?? null,
                        'name' => $plan->name ?? null,
                        'days' => $plan->days ?? null,
                        'data_limit_gb' => $plan->data_limit_gb ?? null,
                        'protocol' => $plan->protocol ?? null,
                    ],
                    'inbound' => [ 'id' => null, 'protocol' => null, 'port' => null ],
                    'client' => [ 'email' => null, 'enable' => false, 'limit_ip' => 0, 'uuid' => null ],
                    'status' => 'pending',
                    'status_text' => 'Awaiting provisioning',
                    'status_color' => 'warning',
                    'latency' => 0,
                    'uptime' => 0,
                    'last_check' => now(),
                    'data_usage' => [
                        'upload' => 0,
                        'download' => 0,
                        'total_bytes' => 0,
                        'total' => $this->formatBytes(0),
                        'used_mb' => 0,
                        'limit_mb' => is_numeric($plan->data_limit_gb ?? null) ? ((float)$plan->data_limit_gb) * 1024.0 : null,
                        'usage_percent' => null,
                    ],
                    'connection_details' => [
                        'ip' => optional($server)->ip,
                        'port' => optional($server)->port ?? optional($server)->panel_port,
                        'host' => optional($server)->host,
                        'uuid' => null,
                    ],
                    'order_info' => [
                        'order_id' => $item->order_id,
                        'expires_at' => $item->expires_at,
                        'days_remaining' => ($item->expires_at) ? now()->diffInDays(Carbon::parse($item->expires_at), false) : null,
                    ],
                ];
            });

            // Merge client rows and order rows, avoiding duplicates by plan id or name+location
            $all = collect();
            foreach ($clientRows as $row) {
                $all->put('client_'.$row['id'], $row);
            }
            foreach ($orderRows as $row) {
                $dupKey = 'plan_'.($row['plan']['id'] ?? $row['name'].'_'.$row['location']);
                if (!$all->has($dupKey)) {
                    $all->put($dupKey, $row);
                }
            }

            $result = $all->values();
            if ($this->showOfflineOnly) {
                $result = $result->where('status', 'offline');
            }
            return $result->sortBy('name')->values()->all();
        });
    }

    public function testSingle(string $id): void
    {
        $proxy = collect($this->proxyStatuses)->firstWhere('id', $id);
        if (!$proxy || !is_string($proxy['id']) && !is_array($proxy)) {
            return;
        }
        // Only handle real clients (skip pending order rows with id like 'order_item_*')
        if (is_string($proxy['id']) && str_starts_with($proxy['id'], 'order_item_')) {
            return;
        }
        $result = $this->testProxyConnection($proxy);
        $this->refreshSilently();
        Notification::make()
            ->title('Test ' . ($result['success'] ? 'Succeeded' : 'Failed'))
            ->body($result['success'] ? ("Latency: {$result['latency']}ms") : ($result['error'] ?? 'Unknown error'))
            ->{($result['success'] ? 'success' : 'warning')}()
            ->send();
    }

    public function refreshSilently(): void
    {
        $this->loadProxyStatuses(true);
        $this->calculateOverallMetrics();
        $this->checkAlertsAndIssues();
        // No notifications; used for auto-polling
    }

    /**
     * Retrieve live client traffic from XUI with short caching.
     * Falls back to locally stored remote_* fields if API not reachable.
     */
    protected function retrieveClientTraffic(ServerClient $client, bool $force = false): ?array
    {
        try {
            $cache = new CacheService();
            if (!$force) {
                $cached = $cache->getClientTraffic($client->id);
                if (is_array($cached)) {
                    return $cached;
                }
            }
            if (!$client->server) {
                return null;
            }
            $svc = new XUIService($client->server);
            // Prefer UUID-based lookup; fall back to email
            $remote = $svc->getClientByUuid($client->id) ?: ($client->email ? $svc->getClientByEmail($client->email) : null);
            if (is_array($remote)) {
                $up = (int) ($remote['up'] ?? 0);
                $down = (int) ($remote['down'] ?? 0);
                $total = (int) ($remote['total'] ?? ($up + $down));
                $result = ['up' => $up, 'down' => $down, 'total' => $total];
                $cache->cacheClientTraffic($client->id, $result);
                return $result;
            }
        } catch (\Throwable $e) {
            // ignore, fallback below
        }
        if (is_numeric($client->remote_up) || is_numeric($client->remote_down)) {
            $up = (int) ($client->remote_up ?? 0);
            $down = (int) ($client->remote_down ?? 0);
            return ['up' => $up, 'down' => $down, 'total' => $up + $down];
        }
        return null;
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
    $totalDataUsage = $proxies->sum('data_usage.total_bytes');

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

        $this->performanceHistory = Cache::remember($cacheKey, 300, function () use ($customer) {
            $timeRange = $this->getTimeRangeData();
            $start = $timeRange['start'];
            $end = $timeRange['end'];
            $interval = $timeRange['interval'];
            $points = $timeRange['points'];
            $format = $timeRange['format'];

            // Pull persisted metrics and aggregate into time buckets
            $metrics = \App\Models\ClientMetric::query()
                ->where('customer_id', $customer->id)
                ->whereBetween('measured_at', [$start, $end])
                ->orderBy('measured_at')
                ->get(['is_online', 'latency_ms', 'total_bytes', 'measured_at']);

            $history = [];
            for ($i = 0; $i < $points; $i++) {
                $bucketStart = $start->copy()->addSeconds($i * $interval);
                $bucketEnd = $bucketStart->copy()->addSeconds($interval);
                $bucket = $metrics->filter(function ($m) use ($bucketStart, $bucketEnd) {
                    return $m->measured_at >= $bucketStart && $m->measured_at < $bucketEnd;
                });

                $total = max(1, $bucket->count());
                $online = $bucket->where('is_online', true)->count();
                $avgLatency = (int) round($bucket->avg('latency_ms'));
                $sumBytes = (int) $bucket->sum('total_bytes');

                $history[] = [
                    'timestamp' => $bucketStart->toISOString(),
                    'formatted_time' => $bucketStart->format($format),
                    'uptime_percentage' => round(($online / $total) * 100, 1),
                    'average_latency' => $avgLatency > 0 ? $avgLatency : null,
                    'active_connections' => $online,
                    'data_transfer' => $sumBytes,
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

            // Prefer cached/live flags first to avoid excessive remote calls
            if (!is_null($client->is_online)) {
                $online = (bool) $client->is_online;
                return [
                    'status' => $online ? 'online' : 'offline',
                    'text' => $online ? 'Connected' : 'Disconnected',
                    'color' => $online ? 'success' : 'danger',
                    'latency' => $online ? $this->measureLatency($client->server) : 0,
                    'uptime' => $this->calculateUptime($client),
                ];
            }

            // Fallback to a lightweight connectivity check via XUI
            $xuiService = new XUIService($client->server);
            $isOnline = $xuiService->testConnection();

            return [
                'status' => $isOnline ? 'online' : 'offline',
                'text' => $isOnline ? 'Connected' : 'Connection failed',
                'color' => $isOnline ? 'success' : 'danger',
                'latency' => $isOnline ? $this->measureLatency($client->server) : 0,
                'uptime' => $this->calculateUptime($client),
            ];
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
        // Quick TCP connect timing as an approximation to latency (no external ping dependency)
        $host = $server->host ?: ($server->ip ?: '127.0.0.1');
        $port = (int) ($server->port ?: ($server->panel_port ?: 443));
        $timeout = 1.0; // seconds
        $start = microtime(true);
        try {
            $errno = 0; $errstr = '';
            $conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
            if ($conn) {
                fclose($conn);
                $ms = (int) round((microtime(true) - $start) * 1000);
                return max(1, min($ms, 2000));
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 0; // unreachable
    }

    protected function calculateUptime(ServerClient $client): float
    {
        // Calculate uptime percentage over the last 24h using persisted metrics
        $from = now()->subDay();
        $to = now();
        $metrics = \App\Models\ClientMetric::query()
            ->where('server_client_id', $client->id)
            ->whereBetween('measured_at', [$from, $to])
            ->get(['is_online']);

        $total = $metrics->count();
        if ($total === 0) {
            return 0.0; // no data yet
        }
        $online = $metrics->where('is_online', true)->count();
        return round(($online / $total) * 100, 1);
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
        // Compare first half vs second half uptime averages in current history
        $history = collect($this->performanceHistory);
        if ($history->isEmpty()) {
            return [
                'direction' => 'stable',
                'percentage_change' => 0,
                'description' => 'No metrics collected yet',
            ];
        }
        $mid = (int) floor($history->count() / 2);
        $first = $history->take($mid)->avg('uptime_percentage') ?: 0;
        $second = $history->slice($mid)->avg('uptime_percentage') ?: 0;
        $change = $first == 0 ? 0 : round((($second - $first) / $first) * 100, 1);
        $dir = $second > $first ? 'up' : ($second < $first ? 'down' : 'stable');
        return [
            'direction' => $dir,
            'percentage_change' => $change,
            'description' => match ($dir) {
                'up' => 'Performance improving',
                'down' => 'Performance declining',
                default => 'Performance stable',
            },
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
        $data = collect($this->proxyStatuses);

        if ($this->showOfflineOnly) {
            $data = $data->where('status', 'offline');
        }

        if ($this->protocolFilter !== 'all') {
            $data = $data->filter(function ($p) {
                return strtolower($p['protocol'] ?? '') === strtolower($this->protocolFilter);
            });
        }

        $term = trim((string) $this->searchTerm);
        if ($term !== '') {
            $low = mb_strtolower($term);
            $data = $data->filter(function ($p) use ($low) {
                $hay = [
                    $p['name'] ?? '',
                    $p['location'] ?? '',
                    $p['plan']['name'] ?? '',
                    $p['client']['email'] ?? '',
                ];
                return collect($hay)->contains(function ($v) use ($low) {
                    return str_contains(mb_strtolower((string) $v), $low);
                });
            });
        }

        return $data->values()->all();
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

    public function getProtocolOptions(): array
    {
        return [
            'all' => 'All',
            'vless' => 'VLESS',
            'vmess' => 'VMESS',
            'trojan' => 'TROJAN',
        ];
    }
}
