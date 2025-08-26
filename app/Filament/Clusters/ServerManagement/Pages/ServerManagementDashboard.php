<?php

namespace App\Filament\Clusters\ServerManagement\Pages;

use UnitEnum;
use BackedEnum;
use App\Filament\Clusters\ServerManagement as ServerManagementCluster;
use App\Services\ServerManagementService;
use App\Models\Server;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\ServerInbound;
use App\Models\ServerClient;

class ServerManagementDashboard extends Page
{
    protected static ?string $cluster = ServerManagementCluster::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';
    protected string $view = 'filament.clusters.server-management.pages.server-management-dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Server Management Dashboard';
    protected static ?int $navigationSort = 0;

    public array $dashboardData = [];
    public array $bulkHealthResults = [];
    public array $limits = [];
    public array $purge = [];
    public bool $pauseLive = false;
    public int $pollIntervalSec = 30;

    protected ServerManagementService $serverManagementService;

    public function boot(ServerManagementService $serverManagementService): void
    {
        $this->serverManagementService = $serverManagementService;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasStaffPermission('manage_servers') || $user->isManager() || $user->isAdmin());
    }

    public function mount(): void
    {
        $this->loadDashboardData();
    $this->initServerLimitsState();
    }

    protected function loadDashboardData(): void
    {
        $this->dashboardData = $this->serverManagementService->getManagementDashboardData();
    }

    protected function initServerLimitsState(): void
    {
        $this->limits = [];
        $cols = ['id','max_clients'];
        if (Schema::hasColumn('servers', 'bandwidth_limit_gb')) {
            $cols[] = 'bandwidth_limit_gb';
        }
        foreach (Server::where('is_active', true)->get($cols) as $s) {
            $this->limits[$s->id] = [
                'max_clients' => (int) ($s->max_clients ?? 0),
                'bandwidth_limit_gb' => (int) (data_get($s, 'bandwidth_limit_gb', 0) ?? 0),
            ];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkHealthCheck')
                ->label('Run Health Check')
                ->icon('heroicon-o-heart')
                ->color('info')
                ->action(function () {
                    $this->runBulkHealthCheck();
                }),

            Action::make('syncInboundsAll')
                ->label('Sync Inbounds (All)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Sync all inbounds from panels?')
                ->modalDescription('This pulls the latest inbounds for all active servers and updates the local DB.')
                ->action(function () {
                    $count = 0; $errors = 0;
                    foreach (Server::where('is_active', true)->get() as $server) {
                        try {
                            $xui = new \App\Services\XUIService($server);
                            $synced = $xui->syncAllInbounds();
                            if (is_numeric($synced)) { $count += (int) $synced; }
                        } catch (\Throwable $e) { $errors++; }
                    }
                    $this->loadDashboardData();
                    Notification::make()
                        ->title('Inbounds Sync Completed')
                        ->body("Synced {$count} inbound(s)" . ($errors ? ", {$errors} server(s) had errors" : ''))
                        ->success()
                        ->send();
                }),

            Action::make('provisionServer')
                ->label('Provision New Server')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    TextInput::make('name')
                        ->label('Server Name')
                        ->required()
                        ->placeholder('e.g., US-East-Gaming-01'),

                    Select::make('country')
                        ->label('Country')
                        ->required()
                        ->options([
                            'US' => 'United States',
                            'UK' => 'United Kingdom',
                            'DE' => 'Germany',
                            'FR' => 'France',
                            'JP' => 'Japan',
                            'SG' => 'Singapore',
                            'CA' => 'Canada',
                            'AU' => 'Australia'
                        ]),

                    TextInput::make('city')
                        ->label('City')
                        ->required()
                        ->placeholder('e.g., New York'),

                    TextInput::make('ip_address')
                        ->label('IP Address')
                        ->required()
                        ->placeholder('e.g., 192.168.1.100'),

                    TextInput::make('panel_url')
                        ->label('X-UI Panel URL')
                        ->required()
                        ->url()
                        ->placeholder('https://your-server.com:54321'),

                    TextInput::make('panel_username')
                        ->label('Panel Username')
                        ->required(),

                    TextInput::make('panel_password')
                        ->label('Panel Password')
                        ->password()
                        ->required(),

                    TextInput::make('max_clients')
                        ->label('Max Clients')
                        ->numeric()
                        ->default(1000),

                    TextInput::make('bandwidth_limit_gb')
                        ->label('Bandwidth Limit (GB)')
                        ->numeric()
                        ->default(1000),
                ])
                ->action(function (array $data) {
                    $this->provisionNewServer($data);
                }),

            Action::make('refreshData')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadDashboardData();

                    Notification::make()
                        ->title('Dashboard refreshed')
                        ->success()
                        ->send();
                }),

            Action::make('forceRefresh')
                ->label('Force Refresh (Live)')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Force refresh live metrics?')
                ->modalDescription('This will clear caches and poll all active servers for fresh metrics. May take a few seconds.')
                ->action(function () {
                    $this->serverManagementService->forceRefreshDashboardCaches();
                    $this->loadDashboardData();

                    Notification::make()
                        ->title('Live metrics refreshed')
                        ->success()
                        ->send();
                }),

            Action::make('backupAllPanels')
                ->label('Backup All Panels')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Trigger backup on all panels?')
                ->modalDescription('Backups will be sent to admins via Telegram if configured.')
                ->action(function () {
                    $ok = 0; $fail = 0;
                    foreach (Server::where('is_active', true)->get() as $server) {
                        try {
                            $xui = new \App\Services\XUIService($server);
                            $xui->createBackup() ? $ok++ : $fail++;
                        } catch (\Throwable $e) { $fail++; }
                    }
                    Notification::make()
                        ->title('Backup requests sent')
                        ->body("Succeeded: {$ok}, Failed: {$fail}")
                        ->color($fail > 0 ? 'warning' : 'success')
                        ->send();
                }),
        ];
    }

    public function runBulkHealthCheck(): void
    {
        try {
            $this->bulkHealthResults = $this->serverManagementService->performBulkHealthCheck();

            $healthyCount = $this->bulkHealthResults['healthy_servers'] ?? 0;
            $totalCount = $this->bulkHealthResults['total_servers'] ?? 0;

            Notification::make()
                ->title('Health Check Completed')
                ->body("{$healthyCount}/{$totalCount} servers are healthy")
                ->success()
                ->send();

            $this->loadDashboardData();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Health Check Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function provisionNewServer(array $data): void
    {
        try {
            $result = $this->serverManagementService->provisionNewServer($data);

            if (($result['success'] ?? false) === true) {
                Notification::make()
                    ->title('Server Provisioned Successfully')
                    ->body("Server '{$data['name']}' has been provisioned and configured")
                    ->success()
                    ->send();

                $this->loadDashboardData();
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error occurred');
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Server Provisioning Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function checkServerHealth(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $healthResult = $this->serverManagementService->checkServerHealth($server);

            $status = $healthResult['status'] ?? 'unknown';
            $responseTime = $healthResult['response_time'] ?? 0;

            Notification::make()
                ->title("Server Health: {$status}")
                ->body("Response time: {$responseTime}ms")
                ->color($status === 'healthy' ? 'success' : ($status === 'warning' ? 'warning' : 'danger'))
                ->send();

            $this->loadDashboardData();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Health Check Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function monitorServerPerformance(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $performanceResult = $this->serverManagementService->monitorServerPerformance($server);

            if (($performanceResult['success'] ?? false) === true) {
                $alertsCount = count($performanceResult['alerts'] ?? []);

                if ($alertsCount > 0) {
                    $alertTypes = collect($performanceResult['alerts'])
                        ->pluck('type')
                        ->join(', ');

                    Notification::make()
                        ->title('Performance Alerts Detected')
                        ->body("{$alertsCount} alerts: {$alertTypes}")
                        ->warning()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Server Performance Normal')
                        ->body('No performance issues detected')
                        ->success()
                        ->send();
                }
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Performance Monitoring Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getDashboardSummary(): array
    {
        return $this->dashboardData['summary'] ?? [
            'total_servers' => 0,
            'active_servers' => 0,
            'healthy_servers' => 0,
            'servers_with_alerts' => 0,
            'total_clients' => 0,
            'total_bandwidth_gb' => 0,
            'average_response_time' => 0,
            'overall_uptime' => 0
        ];
    }

    public function getServersByStatus(): array
    {
        return $this->dashboardData['servers_by_status'] ?? [
            'healthy' => 0,
            'warning' => 0,
            'unhealthy' => 0,
            'offline' => 0,
            'provisioning' => 0
        ];
    }

    public function getGeographicDistribution(): array
    {
        return $this->dashboardData['geographic_distribution'] ?? [];
    }

    public function getTopPerformingServers(): array
    {
        return $this->dashboardData['top_performing_servers'] ?? [];
    }

    public function getServersNeedingAttention(): array
    {
        return $this->dashboardData['servers_needing_attention'] ?? [];
    }

    public function getBulkHealthResults(): array
    {
        return $this->bulkHealthResults;
    }

    public function getRecentAlerts(): array
    {
        return $this->dashboardData['recent_alerts'] ?? [];
    }

    protected function getViewData(): array
    {
        return [
            'summary' => $this->getDashboardSummary(),
            'serversByStatus' => $this->getServersByStatus(),
            'geographicDistribution' => $this->getGeographicDistribution(),
            'topPerformingServers' => $this->getTopPerformingServers(),
            'serversNeedingAttention' => $this->getServersNeedingAttention(),
            'bulkHealthResults' => $this->getBulkHealthResults(),
            'recentAlerts' => $this->getRecentAlerts(),
            'serverMetrics' => $this->getServerMetrics(),
            'inboundUtilization' => $this->getInboundUtilization(),
            'clientsAtRisk' => $this->getClientsAtRisk(),
            'onlineNowEstimate' => $this->getOnlineNowEstimate(),
            'pauseLive' => $this->pauseLive,
            'pollIntervalSec' => $this->pollIntervalSec,
        ];
    }

    /**
     * Collect per-server live metrics (CPU/Memory/Disk, response, clients).
     * Prefers cached metrics warmed by monitorServerPerformance; falls back to a quick fetch.
     */
    protected function getServerMetrics(): array
    {
        $data = [];
        $select = ['id','name','country','max_clients','panel_url'];
        if (Schema::hasColumn('servers', 'bandwidth_limit_gb')) {
            $select[] = 'bandwidth_limit_gb';
        }
        $servers = Server::where('is_active', true)
            ->withCount('inbounds')
            ->get($select);
        foreach ($servers as $server) {
            $cacheKey = "server_metrics_{$server->id}";
            $metrics = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if (empty($metrics) || !is_array($metrics)) {
                try {
                    $result = $this->serverManagementService->monitorServerPerformance($server, false);
                    $metrics = $result['metrics'] ?? [];
                } catch (\Throwable $e) {
                    $metrics = [];
                }
            }
            $data[] = [
                'id' => $server->id,
                'name' => $server->name,
                'country' => $server->country,
                'inbounds_count' => (int) ($server->inbounds_count ?? 0),
                'max_clients' => (int) ($server->max_clients ?? 0),
                'bandwidth_limit_gb' => (int) (data_get($server, 'bandwidth_limit_gb', 0) ?? 0),
                'panel_url' => (string) ($server->panel_url ?? ''),
                'cpu_usage' => (int)($metrics['cpu_usage'] ?? 0),
                'memory_usage' => (int)($metrics['memory_usage'] ?? 0),
                'disk_usage' => (int)($metrics['disk_usage'] ?? 0),
                'response_time_ms' => (int)($metrics['response_time_ms'] ?? 0),
                'active_clients' => (int)($metrics['active_clients'] ?? 0),
                'status' => $metrics['status'] ?? ($this->dashboardData['servers_by_status'] ?? [])[$server->id] ?? 'unknown',
            ];
        }
        return $data;
    }

    /**
     * Livewire polling hook – refresh dashboard aggregates and sections periodically.
     */
    public function refreshLiveData(): void
    {
        // Refresh base dashboard aggregates
        $this->loadDashboardData();

        // Recompute key KPI values from live per-server metrics to keep cards real-time
        try {
            $metrics = $this->getServerMetrics();
            if (!empty($metrics)) {
                $healthy = 0;
                $activeClients = 0;
                $respTimes = [];
                foreach ($metrics as $m) {
                    if (($m['status'] ?? null) === 'healthy') { $healthy++; }
                    $activeClients += (int) ($m['active_clients'] ?? 0);
                    $rt = (int) ($m['response_time_ms'] ?? 0);
                    if ($rt > 0) { $respTimes[] = $rt; }
                }
                $avgRt = !empty($respTimes) ? (int) round(array_sum($respTimes) / max(count($respTimes), 1)) : 0;

                // Inject live numbers into the summary used by the Blade view
                $this->dashboardData['summary']['healthy_servers'] = $healthy;
                $this->dashboardData['summary']['total_clients'] = $activeClients;
                $this->dashboardData['summary']['average_response_time'] = $avgRt;
            }
        } catch (\Throwable $e) {
            // Keep base aggregates if live recompute fails
        }
    }

    /**
     * Sync all inbounds from the X-UI panel to local DB for a given server.
     */
    public function syncInbounds(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $xui = new \App\Services\XUIService($server);
            $count = $xui->syncAllInbounds();

            Notification::make()
                ->title('Inbounds Synced')
                ->body("{$count} inbounds synced from panel")
                ->success()
                ->send();

            $this->loadDashboardData();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Sync Inbounds Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Reset all traffics (all inbounds/clients) on a given server's panel.
     */
    public function resetAllTraffics(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $xui = new \App\Services\XUIService($server);
            $ok = (bool) $xui->resetAllTraffics();

            Notification::make()
                ->title($ok ? 'All Traffics Reset' : 'Reset All Traffics')
                ->body($ok ? 'Done' : 'Panel returned a non-success response')
                ->color($ok ? 'success' : 'warning')
                ->send();

            $this->loadDashboardData();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Reset Traffics Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /** Reset all clients traffic of a specific inbound on a server */
    public function resetInboundTraffics(int $serverId, int $inboundLocalId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $inbound = ServerInbound::where('server_id', $serverId)->findOrFail($inboundLocalId);
            $remoteId = (int) ($inbound->remote_id ?? 0);
            if ($remoteId <= 0) { throw new \RuntimeException('Inbound has no remote_id mapped'); }
            $xui = new \App\Services\XUIService($server);
            $ok = $xui->resetAllClientTraffics($remoteId);
            Notification::make()->title($ok ? 'Inbound Traffic Reset' : 'Inbound Reset Failed')->color($ok ? 'success' : 'danger')->send();
            $this->loadDashboardData();
        } catch (\Throwable $e) {
            Notification::make()->title('Reset Inbound Failed')->body($e->getMessage())->danger()->send();
        }
    }

    /** Reset a single client traffic by email on inbound */
    public function resetClientTraffic(int $serverId, int $inboundLocalId, string $email): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $inbound = ServerInbound::where('server_id', $serverId)->findOrFail($inboundLocalId);
            $remoteId = (int) ($inbound->remote_id ?? 0);
            if ($remoteId <= 0) { throw new \RuntimeException('Inbound has no remote_id mapped'); }
            $xui = new \App\Services\XUIService($server);
            $result = $xui->resetClientTraffic($remoteId, $email);
            $ok = is_array($result) ? ($result['success'] ?? false) : (bool) $result;
            Notification::make()->title($ok ? 'Client Traffic Reset' : 'Client Reset Failed')->color($ok ? 'success' : 'danger')->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Client Reset Failed')->body($e->getMessage())->danger()->send();
        }
    }

    /** Toggle inbound enable/disable on panel and reflect locally */
    public function toggleInboundEnable(int $serverId, int $inboundLocalId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $inbound = ServerInbound::where('server_id', $serverId)->findOrFail($inboundLocalId);
            $remoteId = (int) ($inbound->remote_id ?? 0);
            if ($remoteId <= 0) { throw new \RuntimeException('Inbound has no remote_id mapped'); }

            $xui = new \App\Services\XUIService($server);
            $remote = $xui->getInbound($remoteId);
            if (empty($remote)) { throw new \RuntimeException('Failed to fetch inbound from panel'); }

            $newEnable = !((bool) ($remote['enable'] ?? $inbound->enable ?? true));
            $remote['enable'] = $newEnable;
            $updated = $xui->updateInbound($remoteId, $remote);
            $success = is_array($updated) && !empty($updated);
            if ($success) {
                $inbound->enable = $newEnable;
                $inbound->save();
            }
            Notification::make()->title($newEnable ? 'Inbound Enabled' : 'Inbound Disabled')->color($success? 'success':'warning')->send();
            $this->loadDashboardData();
        } catch (\Throwable $e) {
            Notification::make()->title('Toggle Inbound Failed')->body($e->getMessage())->danger()->send();
        }
    }

    /** Get inbound utilization data for the dashboard */
    protected function getInboundUtilization(): array
    {
        $rows = [];
        $inbounds = ServerInbound::with('server')
            ->whereHas('server', fn($q) => $q->where('is_active', true))
            ->orderBy('current_clients', 'desc')
            ->limit(20)
            ->get(['id','server_id','remote_id','enable','port','protocol','remark','capacity','current_clients','status']);
        foreach ($inbounds as $ib) {
            $cap = $ib->capacity;
            $cur = (int) ($ib->current_clients ?? 0);
            $util = ($cap && $cap > 0) ? min(100, (int) round(($cur / $cap) * 100)) : null; // null = unlimited
            $rows[] = [
                'id' => $ib->id,
                'server_id' => $ib->server_id,
                'remote_id' => (int) ($ib->remote_id ?? 0),
                'server_name' => optional($ib->server)->name,
                'port' => $ib->port,
                'protocol' => $ib->protocol,
                'remark' => $ib->remark,
                'capacity' => $cap,
                'current' => $cur,
                'utilization' => $util,
                'status' => $ib->status,
                'enable' => (bool) ($ib->enable ?? true),
            ];
        }
        return $rows;
    }

    /** List clients at risk (high usage / near expiry) */
    protected function getClientsAtRisk(): array
    {
        $rows = [];
        $clients = ServerClient::with(['inbound.server'])
            ->where(function ($q) {
                $q->where('traffic_percentage_used', '>=', 90)->orWhereNotNull('expiry_time');
            })
            ->orderByDesc('traffic_percentage_used')
            ->limit(20)
            ->get(['id','email','server_inbound_id','traffic_used_mb','traffic_percentage_used','expiry_time']);
        foreach ($clients as $c) {
            $expMs = $c->expiry_time;
            $exp = is_numeric($expMs) && $expMs > 0 ? Carbon::createFromTimestampMs((int) $expMs, 'UTC') : null;
            $rows[] = [
                'id' => (string) $c->id,
                'email' => $c->email,
                'inbound_id' => $c->server_inbound_id,
                'server_id' => optional($c->inbound)->server_id,
                'server_name' => optional(optional($c->inbound)->server)->name,
                'traffic_used_mb' => (float) ($c->traffic_used_mb ?? 0),
                'traffic_percentage_used' => (float) ($c->traffic_percentage_used ?? 0),
                'expiry_human' => $exp ? $exp->diffForHumans() : null,
            ];
        }
        return $rows;
    }

    /** Sum approximate online clients from cache */
    protected function getOnlineNowEstimate(): int
    {
        $sum = 0;
        foreach (Server::where('is_active', true)->pluck('id') as $sid) {
            $list = Cache::get("server_onlines_{$sid}");
            if (is_array($list)) { $sum += count($list); }
        }
        return $sum;
    }

    /**
     * Update server limits (max clients, bandwidth).
     */
    public function updateServerLimits(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $payload = [
                'limits' => [
                    'max_clients' => (int)($this->limits[$serverId]['max_clients'] ?? $server->max_clients),
                    'bandwidth_limit_gb' => (int)($this->limits[$serverId]['bandwidth_limit_gb'] ?? $server->bandwidth_limit_gb),
                ]
            ];
            $result = $this->serverManagementService->manageServerConfiguration($server, $payload);

            Notification::make()
                ->title('Server Limits Updated')
                ->body($result['message'] ?? 'Limits saved')
                ->success()
                ->send();

            $this->initServerLimitsState();
            $this->loadDashboardData();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Update Limits Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Create X-UI database backup and send to admins (if bot configured).
     */
    public function createPanelBackup(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $xui = new \App\Services\XUIService($server);
            $ok = $xui->createBackup();

            Notification::make()
                ->title($ok ? 'Backup Triggered' : 'Backup Request Sent')
                ->body($ok ? 'Backup will be sent to admins' : 'Requested backup on panel')
                ->color($ok ? 'success' : 'info')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Backup Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Delete depleted clients for a provided inbound ID on a server.
     */
    public function deleteDepletedClients(int $serverId, int $inboundLocalId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $inbound = ServerInbound::where('server_id', $serverId)->findOrFail($inboundLocalId);
            $remoteId = (int) ($inbound->remote_id ?? 0);
            if ($remoteId <= 0) { throw new \RuntimeException('Inbound has no remote_id mapped'); }
            $xui = new \App\Services\XUIService($server);
            $ok = $xui->deleteDepletedClients($remoteId);
            Notification::make()
                ->title($ok ? 'Depleted Clients Deleted' : 'Delete Request Sent')
                ->body($ok ? "Inbound #{$inboundLocalId} cleaned" : 'Request completed')
                ->color($ok ? 'success' : 'info')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Delete Depleted Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
