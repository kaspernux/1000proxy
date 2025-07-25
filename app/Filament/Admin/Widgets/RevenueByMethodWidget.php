<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class RevenueByMethodWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue by Payment Method';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $biService = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_payment_chart', 300, function () use ($biService) {
            return $biService->getDashboardAnalytics('30_days');
        });

        $revenueData = $analytics['data']['revenue'] ?? [];
        $revenueByMethod = collect($revenueData['revenue_by_method'] ?? []);

        $labels = $revenueByMethod->keys()->map(function ($method) {
            return ucfirst($method);
        })->toArray();

        $data = $revenueByMethod->pluck('revenue')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
