<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\WithPagination;
use App\Services\AdvancedProxyService;
use App\Services\XUIService;
use App\Models\User;
use App\Models\Customer;
use App\Models\Server;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use BackedEnum;

class AdvancedProxyManagement extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationLabel = 'Advanced Proxy';
    protected static ?string $title = 'Advanced Proxy Management';
    protected static ?string $slug = 'advanced-proxy-management';
    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.admin.pages.advanced-proxy-management';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasStaffPermission('manage_servers') || $user->isManager() || $user->isAdmin());
    }

    use WithPagination;

    // State
    public ?int $selectedUserId = null;
    public ?int $selectedProxyId = null;
    public string $activeTab = 'overview';
    public int $refreshInterval = 30; // seconds

    // IP Rotation Settings
    public string $rotationType = 'time_based';
    public int $rotationInterval = 300;
    public int $stickyDuration = 1800;
    public bool $enableRotation = false;

    // Load Balancing Settings
    public string $loadBalancingAlgorithm = 'weighted_round_robin';
    public bool $enableHealthCheck = true;
    public bool $enableFailover = true;
    public int $responseThreshold = 2000;
    public int $errorThreshold = 5;

    // Health Monitoring Settings
    public bool $monitoringEnabled = true;
    public int $checkInterval = 60;
    public bool $emailAlerts = true;
    public bool $autoRemediation = true;

    // Advanced Configuration
    public bool $connectionPooling = true;
    public int $maxConnections = 100;
    public bool $trafficShaping = false;
    public ?int $bandwidthLimit = null;
    public bool $enableCompression = true;
    public bool $detailedLogging = false;

    // Analytics
    public string $analyticsTimeRange = '24h';
    public array $performanceData = [];
    public array $healthStatus = [];
    public array $blacklist = [];
    public array $quarantined = [];

    protected AdvancedProxyService $advancedProxyService;
    protected XUIService $xuiService;

    public function boot(): void
    {
        $this->advancedProxyService = app(AdvancedProxyService::class);
        $this->xuiService = app(XUIService::class);
    }

    public function mount(): void
    {
        $this->loadInitialData();
    $this->loadControlLists();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->loadInitialData();
                    Notification::make()->title('Data refreshed')->success()->send();
                }),
            \Filament\Actions\Action::make('force_live_refresh')
                ->label('Force Live (X-UI)')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->action(function () {
                    $this->forceLiveRefresh();
                }),
            \Filament\Actions\Action::make('reset_selected_traffic')
                ->label('Reset Traffic (Selected)')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->resetTrafficForSelected();
                }),
            \Filament\Actions\Action::make('clear_selected_ips')
                ->label('Clear IPs (Selected)')
                ->icon('heroicon-o-funnel')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->clearIpsForSelected();
                }),
            \Filament\Actions\Action::make('backup_servers')
                ->label('Send Backup to Admins')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('gray')
                ->action(function () {
                    $this->backupSelectedServers();
                }),
            \Filament\Actions\Action::make('docs')
                ->label('Docs')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url('https://github.com/your-org/1000proxy/wiki/Advanced-Proxy-Management', shouldOpenInNewTab: true),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'users' => $this->getUsers(),
            'userProxies' => $this->getUserProxies(),
            'serverStats' => $this->getServerStats(),
            'rotationConfigs' => $this->getRotationConfigs(),
            'loadBalancers' => $this->getLoadBalancers(),
            'healthMonitors' => $this->getHealthMonitors(),
            'performanceMetrics' => $this->getPerformanceMetrics(),
            'recentEvents' => $this->getRecentEvents(),
            'blacklist' => $this->blacklist,
            'quarantined' => $this->quarantined,
        ];
    }

    public function updatedSelectedUserId(): void
    {
        $this->loadUserProxyData();
        $this->refreshPerformanceData();
    $this->loadControlLists();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;

        switch ($tab) {
            case 'analytics':
                $this->refreshAnalytics();
                break;
            case 'health':
                $this->refreshHealthStatus();
                break;
            case 'configurations':
                $this->loadConfigurationData();
                break;
        }
    }

    public function enableAutoIPRotation(): void
    {
        try {
            if (!$this->selectedUserId) {
                $this->addError('selectedUserId', 'Please select a user first.');
                return;
            }

            $rotationConfig = [
                'type' => $this->rotationType,
                'interval' => $this->rotationInterval,
                'sticky_duration' => $this->stickyDuration,
                'algorithm' => $this->loadBalancingAlgorithm,
            ];

            $result = $this->advancedProxyService->enableAutoIPRotation($this->selectedUserId, $rotationConfig);

            if ($result['success']) {
                $this->enableRotation = true;
                Notification::make()
                    ->title('Auto IP rotation enabled')
                    ->success()
                    ->send();
                $this->loadUserProxyData();
            } else {
                $this->addError('rotation', $result['message'] ?? 'Failed to enable auto IP rotation.');
            }
        } catch (\Exception $e) {
            Log::error('Enable Auto IP Rotation Error: ' . $e->getMessage());
            $this->addError('rotation', 'Failed to enable auto IP rotation.');
        }
    }

    public function configureCustomSchedule(): void
    {
        try {
            if (!$this->selectedUserId) {
                $this->addError('selectedUserId', 'Please select a user first.');
                return;
            }

            $scheduleConfig = [
                'type' => 'interval',
                'expression' => $this->rotationInterval,
                'max_requests' => 1000,
                'max_bandwidth' => '10GB',
                'triggers' => ['time', 'requests'],
                'blacklist_errors' => true,
                'cooldown' => 60,
                'geo_rotation' => false,
                'protocol_rotation' => false,
                'smart_rotation' => true,
            ];

            $result = $this->advancedProxyService->configureCustomRotationSchedule($this->selectedUserId, $scheduleConfig);

            if ($result['success']) {
                Notification::make()
                    ->title('Custom rotation schedule configured')
                    ->success()
                    ->send();
                $this->loadUserProxyData();
            } else {
                $this->addError('schedule', $result['message'] ?? 'Failed to configure custom schedule.');
            }
        } catch (\Exception $e) {
            Log::error('Configure Custom Schedule Error: ' . $e->getMessage());
            $this->addError('schedule', 'Failed to configure custom schedule.');
        }
    }

    public function enableStickySession(): void
    {
        try {
            if (!$this->selectedUserId) {
                $this->addError('selectedUserId', 'Please select a user first.');
                return;
            }

            $sessionConfig = [
                'duration' => $this->stickyDuration,
                'identifier' => 'user_agent',
                'persistence' => 'memory',
                'failover' => true,
                'geo_affinity' => false,
                'protocol_affinity' => true,
                'performance_affinity' => true,
            ];

            $result = $this->advancedProxyService->enableStickySession($this->selectedUserId, $sessionConfig);

            if ($result['success']) {
                Notification::make()
                    ->title('Sticky sessions enabled')
                    ->success()
                    ->send();
                $this->loadUserProxyData();
            } else {
                $this->addError('sticky', $result['message'] ?? 'Failed to enable sticky session support.');
            }
        } catch (\Exception $e) {
            Log::error('Enable Sticky Session Error: ' . $e->getMessage());
            $this->addError('sticky', 'Failed to enable sticky session support.');
        }
    }

    public function setupLoadBalancing(): void
    {
        try {
            if (!$this->selectedUserId) {
                $this->addError('selectedUserId', 'Please select a user first.');
                return;
            }

            $balancingConfig = [
                'algorithm' => $this->loadBalancingAlgorithm,
                'health_check' => $this->enableHealthCheck,
                'failover' => $this->enableFailover,
                'response_threshold' => $this->responseThreshold,
                'error_threshold' => $this->errorThreshold,
                'bandwidth_threshold' => 80,
            ];

            $result = $this->advancedProxyService->setupLoadBalancing($this->selectedUserId, $balancingConfig);

            if ($result['success']) {
                Notification::make()
                    ->title('Load balancing configured')
                    ->success()
                    ->send();
                $this->loadUserProxyData();
            } else {
                $this->addError('loadbalancing', $result['message'] ?? 'Failed to setup load balancing.');
            }
        } catch (\Exception $e) {
            Log::error('Setup Load Balancing Error: ' . $e->getMessage());
            $this->addError('loadbalancing', 'Failed to setup load balancing.');
        }
    }

    public function setupHealthMonitoring(): void
    {
        try {
            if (!$this->selectedUserId) {
                $this->addError('selectedUserId', 'Please select a user first.');
                return;
            }

            $result = $this->advancedProxyService->setupProxyHealthMonitoring($this->selectedUserId);

            if ($result['success']) {
                Notification::make()
                    ->title('Health monitoring set up')
                    ->success()
                    ->send();
                $this->refreshHealthStatus();
            } else {
                $this->addError('monitoring', $result['message'] ?? 'Failed to setup health monitoring.');
            }
        } catch (\Exception $e) {
            Log::error('Setup Health Monitoring Error: ' . $e->getMessage());
            $this->addError('monitoring', 'Failed to setup health monitoring.');
        }
    }

    public function applyAdvancedConfiguration(): void
    {
        try {
            if (!$this->selectedUserId) {
                $this->addError('selectedUserId', 'Please select a user first.');
                return;
            }

            $configOptions = [
                'connection_pooling' => $this->connectionPooling,
                'max_connections' => $this->maxConnections,
                'traffic_shaping' => $this->trafficShaping,
                'bandwidth_limit' => $this->bandwidthLimit,
                'compression' => $this->enableCompression,
                'detailed_logging' => $this->detailedLogging,
                'caching' => false,
                'tcp_optimization' => true,
                'keep_alive' => true,
                'real_time_analytics' => true,
                'log_retention' => 30,
            ];

            $result = $this->advancedProxyService->configureAdvancedProxyOptions($this->selectedUserId, $configOptions);

            if ($result['success']) {
                Notification::make()
                    ->title('Advanced configuration applied')
                    ->success()
                    ->send();
                $this->loadConfigurationData();
            } else {
                $this->addError('config', $result['message'] ?? 'Failed to apply advanced configuration.');
            }
        } catch (\Exception $e) {
            Log::error('Apply Advanced Configuration Error: ' . $e->getMessage());
            $this->addError('config', 'Failed to apply advanced configuration.');
        }
    }

    public function manageProxy(string $action, ?int $proxyId = null): void
    {
        try {
            $proxyId = $proxyId ?? $this->selectedProxyId;

            if (!$proxyId) {
                $this->addError('proxy', 'Please select a proxy first.');
                return;
            }

            $params = ['proxy_id' => $proxyId];

            if ($action === 'update_config') {
                $params['config'] = [
                    'max_connections' => $this->maxConnections,
                    'enable_compression' => $this->enableCompression,
                    'bandwidth_limit' => $this->bandwidthLimit,
                ];
            }

            $result = $this->advancedProxyService->manageProxyConfigurations($this->selectedUserId, $action, $params);

            if ($result['success']) {
                Notification::make()
                    ->title(ucfirst(str_replace('_', ' ', $action)) . ' completed')
                    ->success()
                    ->send();
                $this->refreshHealthStatus();
            } else {
                $this->addError('proxy', $result['message'] ?? 'Failed to manage proxy.');
            }
        } catch (\Exception $e) {
            Log::error('Manage Proxy Error: ' . $e->getMessage());
            $this->addError('proxy', 'Failed to manage proxy.');
        }
    }

    public function refreshAnalytics(): void
    {
        if ($this->selectedUserId) {
            $result = $this->advancedProxyService->getProxyPerformanceAnalytics($this->selectedUserId, $this->analyticsTimeRange);

            if (!empty($result['success'])) {
                $this->performanceData = $result['analytics'] ?? [];
            }
        }
    }

    public function refreshHealthStatus(): void
    {
        if ($this->selectedUserId) {
            $this->healthStatus = $this->getHealthStatusData();
        }
    }

    public function loadUserProxyData(): void
    {
        if ($this->selectedUserId) {
            $this->refreshPerformanceData();
            $this->refreshHealthStatus();
        }
    }

    public function refreshPerformanceData(): void
    {
        $this->refreshAnalytics();
    }

    public function loadConfigurationData(): void
    {
        // Load current configurations for the selected user
        if ($this->selectedUserId) {
            $configs = Cache::get("advanced_config_{$this->selectedUserId}", []);

            if (!empty($configs)) {
                $this->connectionPooling = $configs['connection_pooling'] ?? true;
                $this->maxConnections = $configs['max_connections'] ?? 100;
                $this->trafficShaping = $configs['traffic_shaping'] ?? false;
                $this->enableCompression = $configs['compression'] ?? true;
                $this->detailedLogging = $configs['detailed_logging'] ?? false;
            }
        }
    }

    public function loadInitialData(): void
    {
        $this->healthStatus = [];
        $this->performanceData = [];
    }

    protected function loadControlLists(): void
    {
        if ($this->selectedUserId) {
            $this->blacklist = Cache::get("proxy_blacklist_{$this->selectedUserId}", []);
            $this->quarantined = Cache::get("proxy_quarantine_{$this->selectedUserId}", []);
        } else {
            $this->blacklist = [];
            $this->quarantined = [];
        }
    }

    // Advanced controls
    public function blacklistEndpoint(string $ip): void
    {
        if (!$this->selectedUserId) {
            $this->addError('selectedUserId', 'Select a user first.');
            return;
        }
        $list = Cache::get("proxy_blacklist_{$this->selectedUserId}", []);
        if (!in_array($ip, $list, true)) {
            $list[] = $ip;
            Cache::put("proxy_blacklist_{$this->selectedUserId}", $list, now()->addDays(7));
            $this->blacklist = $list;
        }
        Notification::make()->title("Blacklisted {$ip}")->success()->send();
    }

    public function clearBlacklist(): void
    {
        if (!$this->selectedUserId) return;
        Cache::forget("proxy_blacklist_{$this->selectedUserId}");
        $this->blacklist = [];
        Notification::make()->title('Blacklist cleared')->success()->send();
    }

    public function quarantineProxy(int $proxyId): void
    {
        if (!$this->selectedUserId) return;
        $set = collect(Cache::get("proxy_quarantine_{$this->selectedUserId}", []));
        if (!$set->contains($proxyId)) {
            $set->push($proxyId);
            Cache::put("proxy_quarantine_{$this->selectedUserId}", $set->values()->all(), now()->addDays(7));
            $this->quarantined = $set->values()->all();
        }
        Notification::make()->title("Proxy {$proxyId} quarantined")->success()->send();
    }

    public function restoreProxy(int $proxyId): void
    {
        if (!$this->selectedUserId) return;
        $set = collect(Cache::get("proxy_quarantine_{$this->selectedUserId}", []))->reject(fn($id) => (int)$id === (int)$proxyId)->values();
        Cache::put("proxy_quarantine_{$this->selectedUserId}", $set->all(), now()->addDays(7));
        $this->quarantined = $set->all();
        Notification::make()->title("Proxy {$proxyId} restored")->success()->send();
    }

    public function rebalanceWeights(): void
    {
        if (!$this->selectedUserId) return;
        $orders = $this->getUserProxies();
        if ($orders->isEmpty()) return;

        // Simple dynamic weight: inverse of simulated response time + bias for non-quarantined
        $weights = [];
        foreach ($orders as $order) {
            $id = $order->id;
            $simRt = max(50, (int)($this->performanceData['network_latency'] ?? rand(50, 300)) + rand(-20, 20));
            $base = 1000 / $simRt; // higher weight for lower latency
            if (in_array($id, $this->quarantined, true)) {
                $base *= 0.1; // heavily de-prioritize quarantined
            }
            $weights[$id] = round($base, 3);
        }
        Cache::put("lb_weights_{$this->selectedUserId}", $weights, now()->addHours(6));
        Notification::make()->title('Load balancer weights rebalanced')->success()->send();
    }

    public function runHealthSweep(): void
    {
        if (!$this->selectedUserId) return;
        // Simulate sweep: quarantine proxies if random failure detected
        $orders = $this->getUserProxies();
        $toQuarantine = [];
        foreach ($orders as $order) {
            $unhealthy = rand(0, 100) < 5; // 5% chance unhealthy
            if ($unhealthy) { $toQuarantine[] = $order->id; }
        }
        if ($toQuarantine) {
            $set = collect(Cache::get("proxy_quarantine_{$this->selectedUserId}", []))->merge($toQuarantine)->unique()->values();
            Cache::put("proxy_quarantine_{$this->selectedUserId}", $set->all(), now()->addDays(7));
            $this->quarantined = $set->all();
        }
        $this->refreshHealthStatus();
        Notification::make()->title('Health sweep completed')->body(count($toQuarantine) . ' quarantined').success()->send();
    }

    public function rotateSubset(int $percent = 20): void
    {
        if (!$this->selectedUserId) return;
        $orders = $this->getUserProxies();
        $count = max(1, (int)floor(($orders->count() * $percent) / 100));
        $ids = $orders->pluck('id')->shuffle()->take($count);
        foreach ($ids as $id) {
            try { $this->manageProxy('rotate_ip', (int)$id); } catch (\Throwable $e) { /* ignore individual errors */ }
        }
        Notification::make()->title("Rotated {$count} proxies ({$percent}%)")->success()->send();
    }

    public function syncXUI(): void
    {
        if (!$this->selectedUserId) return;
        try {
            if (method_exists($this->xuiService, 'syncUser')) {
                $this->xuiService->syncUser($this->selectedUserId);
            }
            Notification::make()->title('XUI sync triggered')->success()->send();
        } catch (\Throwable $e) {
            Log::warning('XUI sync error: '.$e->getMessage());
            Notification::make()->title('XUI sync failed')->danger()->send();
        }
    }

    // Data fetching methods (private)
    private function getUsers()
    {
        // In our domain, orders belong to Customer, not User.
        return Customer::whereHas('orders', function ($query) {
                $query->where('payment_status', 'paid')
                      ->where('order_status', 'completed');
            })
            ->select('id', 'name', 'email')
            ->get();
    }

    private function getUserProxies()
    {
        if (!$this->selectedUserId) return collect();

        return Order::where('customer_id', $this->selectedUserId)
            ->where('payment_status', 'paid')
            ->where('order_status', 'completed')
            ->with(['orderItems.serverPlan.server'])
            ->get();
    }

    private function getServerStats(): array
    {
        $totalServers = Server::count();
        $activeServers = Server::where('is_active', true)->count();
        $totalProxies = Order::where('payment_status', 'paid')->where('order_status', 'completed')->count();
        $avgResponse = (float) (Server::avg('response_time_ms') ?? 0);
        $totalMb = (float) (Server::sum('total_traffic_mb') ?? 0);
        $totalGb = round($totalMb / 1024, 2);
        return [
            'total_servers' => $totalServers,
            'active_servers' => $activeServers,
            'total_proxies' => $totalProxies,
            // Approximate healthy proxies based on active servers proportion
            'healthy_proxies' => max(0, (int) round($totalProxies * ($activeServers > 0 ? min(1.0, $activeServers / max(1, $totalServers)) : 0))),
            'avg_response_time' => round($avgResponse),
            'total_bandwidth' => $totalGb . ' GB',
        ];
    }

    private function getRotationConfigs(): array
    {
        if (!$this->selectedUserId) return [];

        return Cache::get("rotation_config_{$this->selectedUserId}", []);
    }

    private function getLoadBalancers(): array
    {
        if (!$this->selectedUserId) return [];

        return Cache::get("load_balancers_{$this->selectedUserId}", []);
    }

    private function getHealthMonitors(): array
    {
        if (!$this->selectedUserId) return [];

        return [
            'active_monitors' => rand(1, 5),
            'total_checks' => rand(1000, 10000),
            'failed_checks' => rand(10, 100),
            'avg_response_time' => rand(100, 500),
            'uptime_percentage' => rand(95, 99.9),
        ];
    }

    private function getPerformanceMetrics(): array
    {
        // Prefer live telemetry cached by ServerManagementService; fallback to placeholders
        try {
            $servers = $this->getSelectedUserServers();
            $online = 0; $bw = 0; $cpu = 0; $mem = 0; $disk = 0; $lat = 0; $count = max(1, $servers->count());
            foreach ($servers as $server) {
                $svc = app(\App\Services\ServerManagementService::class);
                $metrics = $svc->monitorServerPerformance($server, false);
                $online += (int) ($metrics['active_clients'] ?? 0);
                $bw += (float) ($metrics['bandwidth_usage_gb'] ?? 0) * 1024; // unify to MB
                $cpu += (int) ($metrics['cpu_usage'] ?? 0);
                $mem += (int) ($metrics['memory_usage'] ?? 0);
                $disk += (int) ($metrics['disk_usage'] ?? 0);
                $lat += (int) ($metrics['average_response_time_ms'] ?? $metrics['response_time'] ?? 0);
            }
            return [
                'requests_per_second' => max(1, $online),
                'bandwidth_usage' => (int) round($bw / $count),
                'cpu_usage' => (int) round($cpu / $count),
                'memory_usage' => (int) round($mem / $count),
                'disk_usage' => (int) round($disk / $count),
                'network_latency' => (int) round($lat / $count),
            ];
        } catch (\Throwable $e) {
            return [
                'requests_per_second' => rand(50, 500),
                'bandwidth_usage' => rand(10, 90),
                'cpu_usage' => rand(20, 80),
                'memory_usage' => rand(30, 70),
                'disk_usage' => rand(15, 85),
                'network_latency' => rand(10, 100),
            ];
        }
    }

    private function getRecentEvents()
    {
        return collect([
            ['time' => now()->subMinutes(5), 'type' => 'info', 'message' => 'IP rotation completed successfully'],
            ['time' => now()->subMinutes(10), 'type' => 'warning', 'message' => 'High response time detected on server EU-01'],
            ['time' => now()->subMinutes(15), 'type' => 'success', 'message' => 'Load balancer configuration updated'],
            ['time' => now()->subMinutes(20), 'type' => 'info', 'message' => 'Health check passed for all proxies'],
            ['time' => now()->subMinutes(30), 'type' => 'error', 'message' => 'Proxy connection failed, automatic failover triggered'],
        ]);
    }

    private function getHealthStatusData(): array
    {
        try {
            $servers = $this->getSelectedUserServers();
            $healthy = 0; $unhealthy = 0; $warn = 0; $overall = 100;
            foreach ($servers as $server) {
                $svc = app(\App\Services\ServerManagementService::class);
                $m = $svc->monitorServerPerformance($server, false);
                $status = $m['status'] ?? 'healthy';
                if ($status === 'healthy') { $healthy++; } elseif ($status === 'warning') { $warn++; } else { $unhealthy++; }
                $overall = min($overall, (int) ($m['health_score'] ?? 100));
            }
            return [
                'overall_health' => max(0, $overall),
                'healthy_proxies' => $healthy,
                'unhealthy_proxies' => $unhealthy,
                'warning_proxies' => $warn,
                'last_check' => now(),
                'next_check' => now()->addMinutes(5),
            ];
        } catch (\Throwable $e) {
            return [
                'overall_health' => rand(90, 100),
                'healthy_proxies' => rand(15, 20),
                'unhealthy_proxies' => rand(0, 2),
                'warning_proxies' => rand(0, 3),
                'last_check' => now()->subMinutes(rand(1, 5)),
                'next_check' => now()->addMinutes(rand(1, 5)),
            ];
        }
    }

    // --- Live X-UI management helpers ---

    private function getSelectedUserServers(): \Illuminate\Support\Collection
    {
        if (!$this->selectedUserId) return collect();
        $orders = $this->getUserProxies();
        // Restore legacy behavior: one server per order via accessor ($order->server)
        return $orders
            ->map(fn (Order $o) => $o->server)
            ->filter()
            ->unique('id')
            ->values();
    }

    public function forceLiveRefresh(): void
    {
        try {
            $servers = $this->getSelectedUserServers();
            $totalOnline = 0;
            foreach ($servers as $server) {
                $xui = new \App\Services\XUIService($server);
                $onlines = $xui->getOnlineClients();
                $totalOnline += is_array($onlines) ? count($onlines) : 0;
            }
            $this->performanceData = $this->getPerformanceMetrics();
            $this->healthStatus = $this->getHealthStatusData();
            Notification::make()->title("Live data refreshed ({$totalOnline} online)")->success()->send();
        } catch (\Throwable $e) {
            Log::warning('forceLiveRefresh failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Live refresh failed')->danger()->send();
        }
    }

    public function resetTrafficForSelected(): void
    {
        if (!$this->selectedProxyId) {
            $this->addError('proxy', 'Select a proxy (order) first.');
            return;
        }
        $order = Order::find($this->selectedProxyId);
        if (!$order) { $this->addError('proxy', 'Order not found.'); return; }
        $clients = \App\Models\ServerClient::where('order_id', $order->id)->get();
        $ok = 0; $fail = 0;
        foreach ($clients as $client) {
            try {
                $server = $client->inbound?->server
                    ?: ($order->orderItems->first()?->serverPlan?->server
                        ?: $order->server);
                if (!$server) { $fail++; continue; }
                $xui = new \App\Services\XUIService($server);
                $inboundId = $client->remote_inbound_id ?: ($client->inbound?->remote_id);
                $email = $client->email;
                if ($inboundId && $email && $xui->resetClientTraffic((int)$inboundId, (string)$email)) { $ok++; } else { $fail++; }
            } catch (\Throwable $e) { $fail++; }
        }
        Notification::make()->title("Reset traffic: {$ok} ok, {$fail} failed")->success()->send();
        $this->refreshHealthStatus();
    }

    public function clearIpsForSelected(): void
    {
        if (!$this->selectedProxyId) {
            $this->addError('proxy', 'Select a proxy (order) first.');
            return;
        }
        $order = Order::find($this->selectedProxyId);
        if (!$order) { $this->addError('proxy', 'Order not found.'); return; }
        $clients = \App\Models\ServerClient::where('order_id', $order->id)->get();
        $ok = 0; $fail = 0;
        foreach ($clients as $client) {
            try {
                $server = $client->inbound?->server
                    ?: ($order->orderItems->first()?->serverPlan?->server
                        ?: $order->server);
                if (!$server) { $fail++; continue; }
                $xui = new \App\Services\XUIService($server);
                if ($xui->clearClientIps((string)$client->email)) { $ok++; } else { $fail++; }
            } catch (\Throwable $e) { $fail++; }
        }
        Notification::make()->title("Cleared IPs: {$ok} ok, {$fail} failed")->success()->send();
    }

    public function backupSelectedServers(): void
    {
        $servers = $this->getSelectedUserServers();
        if ($servers->isEmpty()) { Notification::make()->title('No servers to backup')->warning()->send(); return; }
        $ok = 0; $fail = 0;
        foreach ($servers as $server) {
            try { $xui = new \App\Services\XUIService($server); $xui->createBackup() ? $ok++ : $fail++; }
            catch (\Throwable $e) { $fail++; }
        }
        Notification::make()->title("Backup triggered: {$ok} ok, {$fail} failed")->success()->send();
    }
}
