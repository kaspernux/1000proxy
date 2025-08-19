<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class UserSegmentationWidget extends ChartWidget
{
    protected ?string $heading = 'User Segmentation';
    protected static ?int $sort = 8;

    protected function getData(): array
    {
        $userId = auth()->id() ?? 0;
        $filterStateService = app(\App\Services\AnalyticsFilterState::class);
        $filterState = $filterStateService->get($userId);
        $serviceRange = $filterStateService->mapToServiceRange($filterState['time_range'] ?? '30d');

        $bi = app(BusinessIntelligenceService::class);
        $cacheKey = 'bi_user_segments_' . $userId . '_' . $serviceRange;
        $analytics = Cache::remember($cacheKey, 300, fn () => $bi->getDashboardAnalytics($serviceRange));
        $segments = ($analytics['data']['segments']['segments'] ?? []) ?: [];

        $labels = array_keys($segments);
        $data = array_map(fn ($arr) => is_array($arr) ? count($arr) : (is_numeric($arr) ? $arr : 0), $segments);

        return [
            'datasets' => [[
                'label' => 'Users',
                'data' => $data,
                'backgroundColor' => [
                    'rgba(59,130,246,0.8)',
                    'rgba(34,197,94,0.8)',
                    'rgba(245,158,11,0.8)',
                    'rgba(239,68,68,0.8)',
                    'rgba(147,51,234,0.8)',
                ],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
