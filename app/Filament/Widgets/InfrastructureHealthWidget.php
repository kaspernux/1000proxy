<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\MetricsAggregator;
use App\Services\XUIService;
use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * Unified infrastructure widget replacing ServerHealthMonitoringWidget & SystemHealthIndicatorsWidget
 * Focus: server fleet, XUI panels, traffic, active connections trend, average CPU.
 */
class InfrastructureHealthWidget extends BaseWidget
{
    protected static ?int $sort = 2; // after main dashboard stats

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    /**
     * Real-time listeners (wired from Echo in resources/js/echo.js) so widget updates instantly.
     */
    protected $listeners = [
        'serverStatusUpdated' => 'handleServerEvent',
        'refreshInfrastructureHealth' => 'handleServerEvent',
        'orderPaid' => 'handleServerEvent', // minor: might affect active connections
    ];

    protected function getStats(): array
    {
        /** @var MetricsAggregator $metrics */
        $metrics = App::make(MetricsAggregator::class);

        $server = $metrics->serverSummary();
        $traffic = $metrics->serverTraffic();
        $connections = $metrics->activeConnectionsTrend();
        $xui = $this->getXuiPanelStatus();
        $cpu = $this->getAverageCpuLoad();
    // Performance score removed (synthetic). Keep only real-ish metrics.

        return [
            Stat::make('Server Fleet', $server['online'] . '/' . $server['total'] . ' Online')
                ->description($this->serverFleetDescription($server))
                ->descriptionIcon($server['online'] >= ($server['total'] * 0.9) ? 'heroicon-m-check-circle' : ($server['online'] >= ($server['total'] * 0.7) ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-x-circle'))
                ->color($server['online'] >= ($server['total'] * 0.9) ? 'success' : ($server['online'] >= ($server['total'] * 0.7) ? 'warning' : 'danger'))
                ->chart(array_values($server['statusDistribution'])),

            Stat::make('XUI Panels', $xui['percentage'] . '% Connected')
                ->description($xui['connected'] . ' / ' . $xui['total'] . ' panels')
                ->descriptionIcon($xui['percentage'] >= 90 ? 'heroicon-m-globe-alt' : ($xui['percentage'] >= 70 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-x-circle'))
                ->color($xui['percentage'] >= 90 ? 'success' : ($xui['percentage'] >= 70 ? 'warning' : 'danger')),

            Stat::make('Active Connections', number_format($connections['current']))
                ->description($connections['description'])
                ->descriptionIcon($connections['icon'])
                ->color($connections['color'])
                ->chart($connections['chart']),

            Stat::make('System Traffic', $traffic['displayTotal'])
                ->description('↑ ' . $traffic['displayUp'] . ' ↓ ' . $traffic['displayDown'])
                ->descriptionIcon('heroicon-m-signal')
                ->color('info'),

            Stat::make('Avg CPU Load', $cpu['avg'] . '%')
                ->description($cpu['description'])
                ->descriptionIcon($cpu['icon'])
                ->color($cpu['color']),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    private function serverFleetDescription(array $server): string
    {
        $down = ($server['statusDistribution']['down'] ?? 0);
        $paused = ($server['statusDistribution']['paused'] ?? 0);
        return $down . ' down, ' . $paused . ' paused';
    }

    private function getXuiPanelStatus(): array
    {
        return Cache::remember('infra.xui.status', 300, function () {
            $servers = Server::where('status', 'up')->get();
            $connected = 0; $failed = 0;
            foreach ($servers as $server) {
                try {
                    $service = new XUIService($server);
                    if ($service->testConnection()) { $connected++; } else { $failed++; }
                } catch (\Exception $e) { $failed++; }
            }
            $total = $servers->count();
            $percentage = $total > 0 ? round(($connected / $total) * 100, 1) : 0;
            return compact('connected','failed','total','percentage');
        });
    }

    private function getAverageCpuLoad(): array
    {
        return Cache::remember('infra.cpu.avg', 300, function () {
            $servers = Server::where('status','up')->get();
            if ($servers->isEmpty()) {
                return [ 'avg' => 0, 'description' => 'No active servers', 'icon' => 'heroicon-m-cpu-chip', 'color' => 'gray' ];
            }
            $loads = [];
            foreach ($servers as $s) {
                $metrics = $s->performance_metrics;
                $load = null;
                if (is_array($metrics) && isset($metrics['cpu_load'])) { $load = (float)$metrics['cpu_load']; }
                elseif (is_string($metrics)) { $decoded = json_decode($metrics, true); if (isset($decoded['cpu_load'])) $load = (float)$decoded['cpu_load']; }
                if ($load !== null) { $loads[] = $load; }
            }
            $avg = empty($loads) ? 0 : round(array_sum($loads)/count($loads),1);
            $description = $avg === 0 ? 'No telemetry' : ($avg < 50 ? 'Optimal' : ($avg < 70 ? 'Normal' : ($avg < 85 ? 'High load' : 'Critical')));
            $icon = $avg < 85 ? 'heroicon-m-cpu-chip' : 'heroicon-m-fire';
            $color = $avg < 70 ? 'success' : ($avg < 85 ? 'warning' : 'danger');
            return compact('avg','description','icon','color');
        });
    }

    /**
     * Handle any server related broadcast event by clearing relevant caches and refreshing.
     */
    public function handleServerEvent(): void
    {
        foreach (['dash.server.summary','dash.server.traffic','dash.connections.trend.12','infra.xui.status','infra.cpu.avg'] as $key) {
            \Cache::forget($key);
        }
        $this->dispatch('$refresh');
    }
}
