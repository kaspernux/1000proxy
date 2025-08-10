<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class LocationPopularityWidget extends ChartWidget
{
    protected static ?string $heading = 'Top Server Locations';
    protected static ?int $sort = 9;

    protected function getData(): array
    {
        $bi = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_location_popularity', 300, fn () => $bi->getDashboardAnalytics('30_days'));
        $serverData = $analytics['data']['servers'] ?? [];
        $locations = $serverData['popular_locations'] ?? collect();
        $labels = $locations->pluck('location')->toArray();
        $data = $locations->pluck('orders_count')->toArray();
        return [
            'datasets' => [[
                'label' => 'Orders',
                'data' => $data,
                'backgroundColor' => 'rgba(99,102,241,0.8)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
