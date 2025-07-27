<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Customer;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerClient;
use App\Models\Order;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            // Revenue & Financial Stats
            Stat::make('Total Revenue', '$' . number_format($this->getTotalRevenue(), 2))
                ->description('All-time revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($this->getRevenueChart())
                ->color('success'),

            Stat::make('Monthly Revenue', '$' . number_format($this->getMonthlyRevenue(), 2))
                ->description($this->getRevenueGrowth() . '% vs last month')
                ->descriptionIcon($this->getRevenueGrowth() > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getMonthlyRevenueChart())
                ->color($this->getRevenueGrowth() > 0 ? 'success' : 'danger'),

            Stat::make('Pending Payments', '$' . number_format($this->getPendingPayments(), 2))
                ->description(Order::where('payment_status', 'pending')->count() . ' pending orders')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            // Customer Stats
            Stat::make('Total Customers', number_format(Customer::count()))
                ->description($this->getNewCustomersCount() . ' new this month')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart($this->getCustomerChart())
                ->color('info'),

            Stat::make('Active Subscriptions', number_format($this->getActiveSubscriptions()))
                ->description($this->getSubscriptionGrowth() . '% vs last month')
                ->descriptionIcon($this->getSubscriptionGrowth() > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($this->getSubscriptionGrowth() > 0 ? 'success' : 'danger'),

            Stat::make('Customer Retention', number_format($this->getCustomerRetention(), 1) . '%')
                ->description('90-day retention rate')
                ->descriptionIcon('heroicon-m-heart')
                ->color('purple'),

            // Server & Infrastructure Stats
            Stat::make('Total Servers', number_format(Server::count()))
                ->description(Server::where('status', 'up')->count() . ' online')
                ->descriptionIcon('heroicon-m-server')
                ->chart($this->getServerStatusChart())
                ->color('primary'),

            Stat::make('Server Utilization', number_format($this->getServerUtilization(), 1) . '%')
                ->description('Average server load')
                ->descriptionIcon($this->getServerUtilization() > 80 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($this->getServerUtilization() > 80 ? 'danger' : 'success'),

            Stat::make('Active Clients', number_format(ServerClient::where('is_active', true)->count()))
                ->description('Total proxy connections')
                ->descriptionIcon('heroicon-m-link')
                ->chart($this->getClientChart())
                ->color('indigo'),

            // Performance & System Stats
            Stat::make('Total Plans', number_format(ServerPlan::count()))
                ->description(ServerPlan::where('is_active', true)->count() . ' active plans')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('emerald'),

            Stat::make('Conversion Rate', number_format($this->getConversionRate(), 1) . '%')
                ->description('Visitor to customer conversion')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('cyan'),

            Stat::make('System Health', $this->getSystemHealth() . '%')
                ->description('Overall system status')
                ->descriptionIcon($this->getSystemHealth() > 95 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-circle')
                ->color($this->getSystemHealth() > 95 ? 'success' : ($this->getSystemHealth() > 80 ? 'warning' : 'danger')),
        ];
    }

    private function getTotalRevenue(): float
    {
        // Use payment_status 'paid' (per migration), sum grand_amount
        return Order::where('payment_status', 'paid')->sum('grand_amount') ?? 0;
    }

    private function getMonthlyRevenue(): float
    {
        return Order::where('payment_status', 'paid')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('grand_amount') ?? 0;
    }

    private function getRevenueGrowth(): float
    {
        $currentMonth = $this->getMonthlyRevenue();
        $lastMonth = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('grand_amount') ?? 0;

        if ($lastMonth == 0) return $currentMonth > 0 ? 100 : 0;
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    private function getPendingPayments(): float
    {
        return Order::where('payment_status', 'pending')->sum('grand_amount') ?? 0;
    }

    private function getNewCustomersCount(): int
    {
        return Customer::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
    }

    private function getActiveSubscriptions(): int
    {
        // If you have a subscriptions table, count active ones. Otherwise, fallback to active customers.
        // Example: return Subscription::where('status', 'active')->count();
        // Fallback:
        return Customer::where('is_active', true)->count();
    }

    private function getSubscriptionGrowth(): float
    {
        $current = Customer::where('is_active', true)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();
        $last = Customer::where('is_active', true)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->count();
        if ($last == 0) return $current > 0 ? 100 : 0;
        return round((($current - $last) / $last) * 100, 1);
    }

    private function getCustomerRetention(): float
    {
        $total = Customer::count();
        if ($total == 0) return 0;
        $retained = Customer::where('last_login_at', '>=', Carbon::now()->subDays(90))->count();
        return round(($retained / $total) * 100, 1);
    }

    private function getServerUtilization(): float
    {
        $servers = Server::where('status', 'up')->get();
        $total = $servers->sum('total_clients');
        $max = $servers->sum('max_clients_per_inbound');
        if ($max == 0) return 0;
        return round(($total / $max) * 100, 1);
    }

    private function getConversionRate(): float
    {
        $orders = Order::where('payment_status', 'paid')->count();
        $customers = Customer::count();
        if ($customers == 0) return 0;
        return round(($orders / $customers) * 100, 1);
    }

    private function getSystemHealth(): float
    {
        $total = Server::count();
        if ($total == 0) return 0;
        $online = Server::where('status', 'up')->count();
        return round(($online / $total) * 100, 1);
    }

    private function getRevenueChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $data[] = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('grand_amount');
        }
        return $data;
    }

    private function getMonthlyRevenueChart(): array
    {
        $data = [];
        $days = Carbon::now()->daysInMonth;
        for ($i = 1; $i <= $days; $i++) {
            $date = Carbon::now()->startOfMonth()->addDays($i - 1)->toDateString();
            $data[] = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('grand_amount');
        }
        return $data;
    }

    private function getCustomerChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $data[] = Customer::whereDate('created_at', $date)->count();
        }
        return $data;
    }

    private function getServerStatusChart(): array
    {
        $statuses = Server::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->pluck('count', 'status')->toArray();
        return array_values($statuses);
    }

    private function getClientChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $data[] = ServerClient::where('is_active', true)
                ->whereDate('created_at', $date)
                ->count();
        }
        return $data;
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
