<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ServerPerformanceWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $biService = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_server_overview', 300, function () use ($biService) {
            return $biService->getDashboardAnalytics('30_days');
        });

        $serverData = $analytics['data']['servers'] ?? [];
        $healthMetrics = $serverData['health_metrics'] ?? [];

        return [
            Stat::make('Servers Online', number_format($healthMetrics['servers_online'] ?? 0))
                ->description('Active servers')
                ->descriptionIcon('heroicon-m-server')
                ->color('success'),

            Stat::make('Average Uptime', number_format($healthMetrics['average_uptime'] ?? 0, 1) . '%')
                ->description('Server availability')
                ->descriptionIcon('heroicon-m-signal')
                ->color($healthMetrics['average_uptime'] >= 99 ? 'success' : 'warning'),

            Stat::make('Servers Offline', number_format($healthMetrics['servers_offline'] ?? 0))
                ->description('Inactive servers')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($healthMetrics['servers_offline'] > 0 ? 'danger' : 'success'),

            Stat::make('Bandwidth Usage', number_format(($healthMetrics['total_bandwidth_used'] ?? 0) / 1024, 1) . ' GB')
                ->description('Total usage')
                ->descriptionIcon('heroicon-m-arrow-up-down')
                ->color('info'),
        ];
    }
}
