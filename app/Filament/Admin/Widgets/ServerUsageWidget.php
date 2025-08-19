<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class ServerUsageWidget extends ChartWidget
{
    protected ?string $heading = 'Server Usage Distribution';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $biService = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_server_chart', 300, function () use ($biService) {
            return $biService->getDashboardAnalytics('30_days');
        });

        $serverData = $analytics['data']['servers'] ?? [];
        $popularLocations = $serverData['popular_locations'] ?? collect();

        $labels = $popularLocations->pluck('location')->toArray();
        $data = $popularLocations->pluck('orders_count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Orders by Location',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
