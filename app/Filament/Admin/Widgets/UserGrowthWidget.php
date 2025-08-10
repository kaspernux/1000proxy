<?php

namespace App\Filament\Admin\Widgets;

use App\Services\BusinessIntelligenceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class UserGrowthWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id() ?? 0;
        $filterStateService = app(\App\Services\AnalyticsFilterState::class);
        $filterState = $filterStateService->get($userId);
        $serviceRange = $filterStateService->mapToServiceRange($filterState['time_range'] ?? '30d');
        $biService = app(BusinessIntelligenceService::class);
        $cacheKey = 'bi_user_overview_' . $userId . '_' . $serviceRange;
        $analytics = Cache::remember($cacheKey, 300, function () use ($biService, $serviceRange) {
            return $biService->getDashboardAnalytics($serviceRange);
        });

    $userData = $analytics['data']['users'] ?? [];

        return [
            Stat::make('Total Users (30 days)', number_format($userData['total_users'] ?? 0))
                ->description('New registrations')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Active Users', number_format($userData['active_users'] ?? 0))
                ->description('Users with orders')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Conversion Rate', number_format($userData['conversion_rate'] ?? 0, 1) . '%')
                ->description('Users to customers')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($userData['conversion_rate'] >= 20 ? 'success' : 'warning'),

            Stat::make('Customer LTV', '$' . number_format($userData['customer_lifetime_value'] ?? 0, 2))
                ->description('Lifetime value')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),
        ];
    }
}
