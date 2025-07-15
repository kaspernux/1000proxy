<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend (30 Days)';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $biService = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_revenue_chart', 300, function () use ($biService) {
            return $biService->getDashboardAnalytics('30_days');
        });

        $revenueData = $analytics['data']['revenue'] ?? [];
        $dailyRevenue = $revenueData['daily_revenue'] ?? collect();

        $labels = $dailyRevenue->keys()->map(function ($date) {
            return \Carbon\Carbon::parse($date)->format('M j');
        })->toArray();

        $data = $dailyRevenue->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Daily Revenue',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
}
