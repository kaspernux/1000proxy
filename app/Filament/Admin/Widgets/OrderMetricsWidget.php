<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class OrderMetricsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $biService = app(BusinessIntelligenceService::class);
        $analytics = Cache::remember('bi_order_overview', 300, function () use ($biService) {
            return $biService->getDashboardAnalytics('30_days');
        });

        $orderData = $analytics['data']['orders'] ?? [];

        return [
            Stat::make('Total Orders', number_format($orderData['total_orders'] ?? 0))
                ->description('All orders (30 days)')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Completed Orders', number_format($orderData['completed_orders'] ?? 0))
                ->description('Successfully completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Completion Rate', number_format($orderData['completion_rate'] ?? 0, 1) . '%')
                ->description('Success rate')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($orderData['completion_rate'] >= 80 ? 'success' : 'warning'),

            Stat::make('Avg Fulfillment', number_format($orderData['average_fulfillment_time'] ?? 0) . ' min')
                ->description('Processing time')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
