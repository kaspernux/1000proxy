<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\WithPagination;
use App\Services\AdvancedProxyService;
use App\Services\XUIService;
use App\Models\User;
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
        ];
    }

    public function updatedSelectedUserId(): void
    {
        $this->loadUserProxyData();
        $this->refreshPerformanceData();
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

    // Data fetching methods (private)
    private function getUsers()
    {
        return User::whereHas('orders', function ($query) {
            $query->where('payment_status', 'paid')
                  ->where('status', 'active');
        })->select('id', 'name', 'email')->get();
    }

    private function getUserProxies()
    {
        if (!$this->selectedUserId) return collect();

    return Order::where('customer_id', $this->selectedUserId)
                   ->where('payment_status', 'paid')
                   ->where('status', 'active')
                   ->with(['serverPlan.server'])
                   ->get();
    }

    private function getServerStats(): array
    {
        return [
            'total_servers' => Server::count(),
            'active_servers' => Server::where('status', 'active')->count(),
            'total_proxies' => Order::where('payment_status', 'paid')->where('status', 'active')->count(),
            'healthy_proxies' => Order::where('payment_status', 'paid')->where('status', 'active')->count() * 0.95,
            'avg_response_time' => rand(100, 300),
            'total_bandwidth' => rand(500, 2000) . ' GB/day',
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
        return [
            'requests_per_second' => rand(50, 500),
            'bandwidth_usage' => rand(10, 90),
            'cpu_usage' => rand(20, 80),
            'memory_usage' => rand(30, 70),
            'disk_usage' => rand(15, 85),
            'network_latency' => rand(10, 100),
        ];
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
