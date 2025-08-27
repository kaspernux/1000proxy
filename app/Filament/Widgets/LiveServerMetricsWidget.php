<?php

namespace App\Filament\Widgets;

use App\Models\Server;
use App\Models\ServerInbound;
use App\Services\ServerManagementService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class LiveServerMetricsWidget extends Widget
{
    // Match parent signature: non-static $view (PHP 8.3 disallows changing non-static to static on inheritance)
    protected string $view = 'filament.widgets.live-server-metrics-widget';
    protected int|string|array $columnSpan = 'full';

    public ?array $serverMetrics = null;
    public bool $pauseLive = false;
    public int $pollIntervalSec = 20;

    protected ServerManagementService $serverManagementService;

    public function boot(ServerManagementService $svc): void
    {
        $this->serverManagementService = $svc;
    }

    public function mount(): void
    {
        $this->serverMetrics = $this->getServerMetrics();
    }

    protected function getViewData(): array
    {
        return [
            'serverMetrics' => $this->serverMetrics ?? [],
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
        $select = ['id','name','country','max_clients','panel_url','host','panel_port','web_base_path'];
        if (Schema::hasColumn('servers', 'bandwidth_limit_gb')) {
            $select[] = 'bandwidth_limit_gb';
        }
        $servers = Server::where('is_active', true)
            ->withCount('inbounds')
            ->get($select);

        foreach ($servers as $server) {
            $cacheKey = "server_metrics_{$server->id}";
            $metrics = Cache::get($cacheKey);
            if (empty($metrics) || !is_array($metrics)) {
                try {
                    $result = $this->serverManagementService->monitorServerPerformance($server, false);
                    $metrics = $result['metrics'] ?? [];
                } catch (\Throwable $e) {
                    $metrics = [];
                }
            }

            $panelFull = (string) ($server->getPanelBase());
            $parsed = parse_url($panelFull) ?: [];
            $scheme = $parsed['scheme'] ?? 'http';
            $hostOnly = $server->getPanelHost() ?: ($parsed['host'] ?? '');

            $data[] = [
                'id' => $server->id,
                'name' => $server->name,
                'country' => $server->country,
                'inbounds_count' => (int) ($server->inbounds_count ?? 0),
                'max_clients' => (int) ($server->max_clients ?? 0),
                'bandwidth_limit_gb' => (int) (data_get($server, 'bandwidth_limit_gb', 0) ?? 0),
                'base_url' => $hostOnly ? $scheme . '://' . $hostOnly : null,
                'panel_url' => $panelFull,
                'login_attempts' => (int) ($server->login_attempts ?? 0),
                'is_login_locked' => (bool) ($server->isLoginLocked()),
                'has_valid_session' => (bool) ($server->hasValidSession()),
                'cpu_usage' => (int)($metrics['cpu_usage'] ?? 0),
                'memory_usage' => (int)($metrics['memory_usage'] ?? 0),
                'disk_usage' => (int)($metrics['disk_usage'] ?? 0),
                'response_time_ms' => (int)($metrics['response_time_ms'] ?? 0),
                'active_clients' => (int)($metrics['active_clients'] ?? 0),
                'status' => $metrics['status'] ?? 'unknown',
            ];
        }

        return $data;
    }

    public function refreshLiveData(): void
    {
        try {
            $this->serverMetrics = $this->getServerMetrics();
        } catch (\Throwable $e) {
            // swallow
        }
    }

    public function monitorServerPerformance(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $this->serverManagementService->monitorServerPerformance($server, true);
            $this->refreshLiveData();
            Notification::make()->title('Performance refreshed')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Monitor failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function syncInbounds(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $xui = new \App\Services\XUIService($server);
            $count = $xui->syncAllInbounds();
            Notification::make()->title('Inbounds synced')->body("{$count} imported")->success()->send();
            $this->refreshLiveData();
        } catch (\Throwable $e) {
            Notification::make()->title('Sync failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function resetAllTraffics(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $xui = new \App\Services\XUIService($server);
            $ok = (bool) $xui->resetAllTraffics();
            Notification::make()->title($ok ? 'All traffics reset' : 'Reset failed')->color($ok ? 'success' : 'warning')->send();
            $this->refreshLiveData();
        } catch (\Throwable $e) {
            Notification::make()->title('Reset failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function unlockXuiServer(int $serverId): void
    {
        try {
            $server = Server::findOrFail($serverId);
            $server->update([
                'login_attempts' => 0,
                'last_login_attempt_at' => null,
                'session_cookie' => null,
                'session_expires_at' => null,
            ]);
            Notification::make()->title('X-UI login unlocked')->success()->send();
            $this->refreshLiveData();
        } catch (\Throwable $e) {
            Notification::make()->title('Unlock failed')->body($e->getMessage())->danger()->send();
        }
    }
}
