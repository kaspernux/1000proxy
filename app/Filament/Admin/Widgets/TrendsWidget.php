<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class TrendsWidget extends ChartWidget
{
    protected static ?string $heading = 'Key Trends';
    protected static ?int $sort = 12;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $bi = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_trends', 300, fn () => $bi->getDashboardAnalytics('30_days'));
        $trends = $analytics['data']['trends'] ?? [];
        $revenueTrend = $trends['revenue_trend']['daily'] ?? [];
        $labels = array_map(fn($d) => substr($d['date'] ?? '', 5), $revenueTrend);
        $data = array_map(fn($d) => $d['revenue'] ?? 0, $revenueTrend);
        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => $data,
                'backgroundColor' => 'rgba(147,51,234,0.15)',
                'borderColor' => 'rgb(147,51,234)',
                'tension' => 0.3,
                'fill' => true,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
