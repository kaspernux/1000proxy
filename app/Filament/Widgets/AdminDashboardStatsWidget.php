<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\ServerPlan;
use App\Models\Order;
use App\Models\ServerClient;
use App\Services\Dashboard\MetricsAggregator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\App;

class AdminDashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Livewire event listeners to support real-time updates via Echo channels.
     * - refreshRevenueMetrics / orderPaid: invalidate revenue related caches & refresh
     * - serverStatusUpdated / refreshInfrastructureHealth: invalidate server/infra caches & refresh
     */
    protected $listeners = [
        'refreshRevenueMetrics' => 'handleRevenueRefresh',
        'orderPaid' => 'handleRevenueRefresh',
        'serverStatusUpdated' => 'handleServerRefresh',
        'refreshInfrastructureHealth' => 'handleServerRefresh',
    ];

    private function forget(array $keys): void
    {
        foreach ($keys as $k) { \Cache::forget($k); }
    }

    public function handleRevenueRefresh(): void
    {
        $this->forget([
            'dash.revenue.summary',
            'dash.revenue.daily.7',
            'dash.revenue.monthSeries',
        ]);
        // Simple component refresh
        $this->dispatch('$refresh');
    }

    public function handleServerRefresh(): void
    {
        $this->forget([
            'dash.server.summary',
            'dash.server.traffic',
            'dash.connections.trend.12',
            'infra.xui.status',
            'infra.cpu.avg',
        ]);
        $this->dispatch('$refresh');
    }

    protected function getStats(): array
    {
        /** @var MetricsAggregator $metrics */
        $metrics = App::make(MetricsAggregator::class);

        $rev = $metrics->revenueSummary();
        $cust = $metrics->customerSummary();
        $server = $metrics->serverSummary();
        $health = $metrics->systemHealth();

        // Additional lightweight computed values (single queries acceptable)
        $pendingOrdersCount = Order::where('payment_status', 'pending')->count();

        // Conversion rate (keep simple; could be moved to aggregator later)
        $ordersPaid = Order::where('payment_status', 'paid')->count();
        $customersTotal = $cust['total'] ?: 0;
        $conversion = $customersTotal === 0 ? 0 : round(($ordersPaid / $customersTotal) * 100, 1);

        return [
            // Revenue & Financial Stats
            Stat::make('Total Revenue', '$' . number_format($rev['total'], 2))
                ->description('All-time revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($metrics->revenueDailySeries())
                ->color('success'),

            Stat::make('Monthly Revenue', '$' . number_format($rev['monthTotal'], 2))
                ->description($rev['growth'] . '% vs last month')
                ->descriptionIcon($rev['growth'] > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($metrics->revenueMonthSeries())
                ->color($rev['growth'] > 0 ? 'success' : 'danger'),

            Stat::make('Pending Payments', '$' . number_format($rev['pending'], 2))
                ->description($pendingOrdersCount . ' pending orders')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            // Customer Stats
            Stat::make('Total Customers', number_format($cust['total']))
                ->description($cust['newThisMonth'] . ' new this month')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart($metrics->customerDailySeries())
                ->color('info'),

            Stat::make('Active Clients', number_format($server['clients']))
                ->description($cust['subscriptionGrowth'] . '% vs last month')
                ->descriptionIcon($cust['subscriptionGrowth'] > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($cust['subscriptionGrowth'] > 0 ? 'success' : 'danger'),

            Stat::make('Customer Retention', number_format($cust['retention'], 1) . '%')
                ->description('90-day retention rate')
                ->descriptionIcon('heroicon-m-heart')
                ->color('purple'),

            // Server & Infrastructure Stats
            Stat::make('Total Servers', number_format($server['total']))
                ->description($server['online'] . ' online')
                ->descriptionIcon('heroicon-m-server')
                ->chart(array_values($server['statusDistribution']))
                ->color('primary'),

            Stat::make('Server Utilization', number_format($server['utilization'], 1) . '%')
                ->description('Average server load')
                ->descriptionIcon($server['utilization'] > 80 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($server['utilization'] > 80 ? 'danger' : 'success'),

            // Performance & System Stats
            Stat::make('Total Plans', number_format(ServerPlan::count()))
                ->description('All plans')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('emerald'),

            Stat::make('Conversion Rate', number_format($conversion, 1) . '%')
                ->description('Visitor to customer conversion')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('cyan'),

            Stat::make('System Health', $health . '%')
                ->description('Overall system status')
                ->descriptionIcon($health > 95 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-circle')
                ->color($health > 95 ? 'success' : ($health > 80 ? 'warning' : 'danger')),
        ];
    }
    protected function getColumns(): int
    {
        return 3;
    }
}
