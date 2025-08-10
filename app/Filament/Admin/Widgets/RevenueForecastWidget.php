<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class RevenueForecastWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Forecast';
    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $userId = auth()->id() ?? 0;
        $filterStateService = app(\App\Services\AnalyticsFilterState::class);
        $filterState = $filterStateService->get($userId);
        $serviceRange = $filterStateService->mapToServiceRange($filterState['time_range'] ?? '30d');

        $bi = app(BusinessIntelligenceService::class);
        $cacheKey = 'bi_revenue_forecast_' . $userId . '_' . $serviceRange;
        $analytics = Cache::remember($cacheKey, 300, fn () => $bi->getDashboardAnalytics($serviceRange));
        $forecast = $analytics['data']['forecasts'] ?? [];

        $labels = ['Next Month', 'Next Quarter'];
        $data = [
            $forecast['next_month_forecast'] ?? 0,
            $forecast['next_quarter_forecast'] ?? 0,
        ];

        return [
            'datasets' => [[
                'label' => 'Forecasted Revenue',
                'data' => $data,
                'backgroundColor' => 'rgba(16,185,129,0.2)',
                'borderColor' => 'rgb(16,185,129)',
                'borderWidth' => 2,
                'fill' => true,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
