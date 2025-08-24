<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Server;

class ServerStatsOverview extends BaseWidget
{
    public ?Server $record = null;
    protected static bool $isLazy = true;
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $server = $this->record;
        $uptime = $server?->uptime_percentage;
        $resp = $server?->response_time_ms;

        return [
            Stat::make('Status', $server?->status ? ucfirst($server->status) : 'Unknown')
                ->description($server?->health_status ?: '—')
                ->color(match ($server?->status) {
                    'up' => 'success',
                    'down' => 'danger',
                    'paused' => 'secondary',
                    default => 'gray',
                })
                ->icon('heroicon-o-server'),

            Stat::make('Response Time', ($resp !== null ? $resp . ' ms' : '—'))
                ->description('API latency')
                ->color(match (true) {
                    $resp === null => 'gray',
                    $resp < 300 => 'success',
                    $resp < 800 => 'warning',
                    default => 'danger',
                })
                ->icon('heroicon-o-bolt'),

            Stat::make('Uptime', ($uptime !== null ? number_format((float)$uptime, 2) . '%' : '—'))
                ->description('Rolling uptime')
                ->color(match (true) {
                    $uptime === null => 'gray',
                    $uptime >= 99.5 => 'success',
                    $uptime >= 95 => 'warning',
                    default => 'danger',
                })
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
