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
                ->description(Order::where('status', 'pending')->count() . ' pending orders')
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
        return Order::where('status', 'completed')->sum('total') ?? 0;
    }

    private function getMonthlyRevenue(): float
    {
        return Order::where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total') ?? 0;
    }

    private function getRevenueGrowth(): float
    {
        $currentMonth = $this->getMonthlyRevenue();
        $lastMonth = Order::where('status', 'completed')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('total') ?? 0;

        if ($lastMonth == 0) return $currentMonth > 0 ? 100 : 0;
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    private function getPendingPayments(): float
    {
        return Order::where('status', 'pending')->sum('total') ?? 0;
    }

    private function getNewCustomersCount(): int
    {
        return Customer::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
    }

    private function getActiveSubscriptions(): int
    {
        return ServerClient::where('is_active', true)
            ->where('expires_at', '>', Carbon::now())
            ->count();
    }

    private function getSubscriptionGrowth(): float
    {
        $current = $this->getActiveSubscriptions();
        $lastMonth = ServerClient::where('is_active', true)
            ->where('expires_at', '>', Carbon::now()->subMonth())
            ->whereDate('created_at', '<=', Carbon::now()->subMonth()->endOfMonth())
            ->count();

        if ($lastMonth == 0) return $current > 0 ? 100 : 0;
        return round((($current - $lastMonth) / $lastMonth) * 100, 1);
    }

    private function getCustomerRetention(): float
    {
        $totalCustomers = Customer::where('created_at', '<=', Carbon::now()->subDays(90))->count();
        if ($totalCustomers == 0) return 0;

        $retainedCustomers = Customer::where('created_at', '<=', Carbon::now()->subDays(90))
            ->whereHas('orders', function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subDays(90));
            })->count();

        return ($retainedCustomers / $totalCustomers) * 100;
    }

    private function getServerUtilization(): float
    {
        $servers = Server::where('status', 'up')->get();
        if ($servers->isEmpty()) return 0;

        $totalUtilization = $servers->sum(function ($server) {
            $maxClients = $server->max_clients ?? 100;
            $currentClients = $server->total_clients ?? 0;
            return ($currentClients / $maxClients) * 100;
        });

        return $totalUtilization / $servers->count();
    }

    private function getConversionRate(): float
    {
        // Simplified conversion rate calculation
        $totalCustomers = Customer::count();
        $totalOrders = Order::count();

        if ($totalCustomers == 0) return 0;
        return ($totalOrders / $totalCustomers) * 100;
    }

    private function getSystemHealth(): int
    {
        $healthScore = 100;

        // Check server status
        $totalServers = Server::count();
        $onlineServers = Server::where('status', 'up')->count();
        if ($totalServers > 0) {
            $serverHealth = ($onlineServers / $totalServers) * 100;
            $healthScore = min($healthScore, $serverHealth);
        }

        // Check if any servers are overloaded
        $overloadedServers = Server::whereRaw('total_clients > max_clients * 0.9')->count();
        if ($overloadedServers > 0) {
            $healthScore -= ($overloadedServers * 5); // -5% per overloaded server
        }

        return max(0, min(100, round($healthScore)));
    }

    private function getRevenueChart(): array
    {
        return Order::where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('revenue')
            ->toArray();
    }

    private function getMonthlyRevenueChart(): array
    {
        return Order::where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('revenue')
            ->toArray();
    }

    private function getCustomerChart(): array
    {
        return Customer::selectRaw('DATE(created_at) as date, COUNT(*) as customers')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('customers')
            ->toArray();
    }

    private function getServerStatusChart(): array
    {
        return Server::selectRaw('DATE(updated_at) as date, COUNT(*) as servers')
            ->where('status', 'up')
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('servers')
            ->toArray();
    }

    private function getClientChart(): array
    {
        return ServerClient::selectRaw('DATE(created_at) as date, COUNT(*) as clients')
            ->where('is_active', true)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('clients')
            ->toArray();
    }

    protected function getColumns(): int
    {
        return 3;
    }
}