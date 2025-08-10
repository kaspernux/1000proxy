<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class ProtocolUsageWidget extends ChartWidget
{
    protected static ?string $heading = 'Protocol Usage';
    protected static ?int $sort = 10;

    protected function getData(): array
    {
        $bi = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_protocol_usage', 300, fn () => $bi->getDashboardAnalytics('30_days'));
        $serverData = $analytics['data']['servers'] ?? [];
        $protocols = $serverData['protocol_usage'] ?? collect();
        $labels = $protocols->pluck('protocol')->toArray();
        $data = $protocols->pluck('usage_count')->toArray();
        return [
            'datasets' => [[
                'label' => 'Usage',
                'data' => $data,
                'backgroundColor' => [
                    'rgba(34,197,94,0.8)',
                    'rgba(245,158,11,0.8)',
                    'rgba(239,68,68,0.8)',
                    'rgba(59,130,246,0.8)',
                ],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
