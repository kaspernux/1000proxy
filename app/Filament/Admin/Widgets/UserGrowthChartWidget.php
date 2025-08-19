<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class UserGrowthChartWidget extends ChartWidget
{
    protected ?string $heading = 'User Registration Trend';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $biService = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_user_chart', 300, function () use ($biService) {
            return $biService->getDashboardAnalytics('30_days');
        });

        $userData = $analytics['data']['users'] ?? [];
        $dailyRegistrations = $userData['daily_registrations'] ?? collect();

        $labels = $dailyRegistrations->keys()->map(function ($date) {
            return \Carbon\Carbon::parse($date)->format('M j');
        })->toArray();

        $data = $dailyRegistrations->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
