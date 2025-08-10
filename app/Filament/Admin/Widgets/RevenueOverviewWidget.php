<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class RevenueOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id() ?? 0;
        $filterStateService = app(\App\Services\AnalyticsFilterState::class);
        $filterState = $filterStateService->get($userId);
        $serviceRange = $filterStateService->mapToServiceRange($filterState['time_range'] ?? '30d');
        $biService = app(BusinessIntelligenceService::class);
        $cacheKey = 'bi_revenue_overview_' . $userId . '_' . $serviceRange;
        $analytics = Cache::remember($cacheKey, 300, function () use ($biService, $serviceRange) {
            return $biService->getDashboardAnalytics($serviceRange);
        });

    $revenueData = $analytics['data']['revenue'] ?? [];

        return [
            Stat::make('Total Revenue (30 days)', '$' . number_format($revenueData['total_revenue'] ?? 0, 2))
                ->description($this->getGrowthDescription($revenueData['revenue_growth'] ?? 0))
                ->descriptionIcon($this->getGrowthIcon($revenueData['revenue_growth'] ?? 0))
                ->color($this->getGrowthColor($revenueData['revenue_growth'] ?? 0))
                ->chart($this->getRevenueChart($revenueData)),

            Stat::make('Average Order Value', '$' . number_format($revenueData['average_order_value'] ?? 0, 2))
                ->description('Per order')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Monthly Recurring Revenue', '$' . number_format($revenueData['monthly_recurring_revenue'] ?? 0, 2))
                ->description('MRR this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Orders Count', number_format($revenueData['order_count'] ?? 0))
                ->description('Completed orders')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),
        ];
    }

    private function getGrowthDescription($growth): string
    {
        if ($growth > 0) {
            return '+' . number_format($growth, 1) . '% increase';
        } elseif ($growth < 0) {
            return number_format(abs($growth), 1) . '% decrease';
        }
        return 'No change';
    }

    private function getGrowthIcon($growth): string
    {
        return $growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    private function getGrowthColor($growth): string
    {
        return $growth >= 0 ? 'success' : 'danger';
    }

    private function getRevenueChart($revenueData): array
    {
    $dailyRevenue = $revenueData['daily_revenue'] ?? collect();
    return array_values(collect($dailyRevenue)->take(7)->toArray());
    }
}
