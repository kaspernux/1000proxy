<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerClient;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComprehensiveSystemStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalCustomers = Customer::count();
        $newCustomersToday = Customer::where('created_at', '>=', Carbon::today())->count();
        $activeCustomers = Customer::where('is_active', true)->count();

        $totalOrders = Order::count();
        $ordersToday = Order::where('created_at', '>=', Carbon::today())->count();
        $pendingOrders = Order::where('order_status', 'new')->count();
        $totalRevenue = Order::where('payment_status', 'paid')->sum('grand_amount');
        $revenueToday = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::today())
            ->sum('grand_amount');

        $totalServers = Server::count();
        $healthyServers = Server::where('status', 'healthy')->count();
        $offlineServers = Server::where('status', 'offline')->count();

        $totalClients = ServerClient::count();
        $activeClients = ServerClient::where('enable', true)->count();

        $totalStaff = User::count();
        $activeStaff = User::where('is_active', true)->count();

        return [
            // Customer Stats
            Stat::make('👥 Total Customers', number_format($totalCustomers))
                ->description($newCustomersToday > 0 ? "+{$newCustomersToday} new today" : 'No new customers today')
                ->descriptionIcon($newCustomersToday > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->color($newCustomersToday > 0 ? 'success' : 'gray')
                ->chart([
                    Customer::whereBetween('created_at', [Carbon::today()->subDays(6), Carbon::today()->subDays(5)])->count(),
                    Customer::whereBetween('created_at', [Carbon::today()->subDays(5), Carbon::today()->subDays(4)])->count(),
                    Customer::whereBetween('created_at', [Carbon::today()->subDays(4), Carbon::today()->subDays(3)])->count(),
                    Customer::whereBetween('created_at', [Carbon::today()->subDays(3), Carbon::today()->subDays(2)])->count(),
                    Customer::whereBetween('created_at', [Carbon::today()->subDays(2), Carbon::today()->subDays(1)])->count(),
                    Customer::whereBetween('created_at', [Carbon::today()->subDays(1), Carbon::today()])->count(),
                    $newCustomersToday,
                ]),

            // Revenue Stats
            Stat::make('💰 Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description($revenueToday > 0 ? "+\${$revenueToday} today" : 'No revenue today')
                ->descriptionIcon($revenueToday > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->color($revenueToday > 0 ? 'success' : 'gray')
                ->chart([
                    Order::where('payment_status', 'paid')->whereBetween('created_at', [Carbon::today()->subDays(6), Carbon::today()->subDays(5)])->sum('grand_amount'),
                    Order::where('payment_status', 'paid')->whereBetween('created_at', [Carbon::today()->subDays(5), Carbon::today()->subDays(4)])->sum('grand_amount'),
                    Order::where('payment_status', 'paid')->whereBetween('created_at', [Carbon::today()->subDays(4), Carbon::today()->subDays(3)])->sum('grand_amount'),
                    Order::where('payment_status', 'paid')->whereBetween('created_at', [Carbon::today()->subDays(3), Carbon::today()->subDays(2)])->sum('grand_amount'),
                    Order::where('payment_status', 'paid')->whereBetween('created_at', [Carbon::today()->subDays(2), Carbon::today()->subDays(1)])->sum('grand_amount'),
                    Order::where('payment_status', 'paid')->whereBetween('created_at', [Carbon::today()->subDays(1), Carbon::today()])->sum('grand_amount'),
                    $revenueToday,
                ]),

            // Order Stats
            Stat::make('🛒 Total Orders', number_format($totalOrders))
                ->description($pendingOrders > 0 ? "{$pendingOrders} pending orders" : 'No pending orders')
                ->descriptionIcon($pendingOrders > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($pendingOrders > 0 ? 'warning' : 'success')
                ->chart([
                    Order::whereBetween('created_at', [Carbon::today()->subDays(6), Carbon::today()->subDays(5)])->count(),
                    Order::whereBetween('created_at', [Carbon::today()->subDays(5), Carbon::today()->subDays(4)])->count(),
                    Order::whereBetween('created_at', [Carbon::today()->subDays(4), Carbon::today()->subDays(3)])->count(),
                    Order::whereBetween('created_at', [Carbon::today()->subDays(3), Carbon::today()->subDays(2)])->count(),
                    Order::whereBetween('created_at', [Carbon::today()->subDays(2), Carbon::today()->subDays(1)])->count(),
                    Order::whereBetween('created_at', [Carbon::today()->subDays(1), Carbon::today()])->count(),
                    $ordersToday,
                ]),

            // Server Health Stats
            Stat::make('🖥️ Server Health', "{$healthyServers}/{$totalServers} Healthy")
                ->description($offlineServers > 0 ? "{$offlineServers} servers offline" : 'All servers online')
                ->descriptionIcon($offlineServers > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($offlineServers > 0 ? 'danger' : 'success')
                ->chart([
                    $healthyServers,
                    $offlineServers,
                    Server::where('status', 'warning')->count(),
                    Server::where('status', 'maintenance')->count(),
                ]),

            // Active Clients Stats
            Stat::make('👤 Active Clients', number_format($activeClients) . '/' . number_format($totalClients))
                ->description(round(($activeClients / max($totalClients, 1)) * 100, 1) . '% active')
                ->descriptionIcon('heroicon-m-users')
                ->color($activeClients > ($totalClients * 0.7) ? 'success' : 'warning')
                ->chart([
                    ServerClient::whereBetween('created_at', [Carbon::today()->subDays(6), Carbon::today()->subDays(5)])->count(),
                    ServerClient::whereBetween('created_at', [Carbon::today()->subDays(5), Carbon::today()->subDays(4)])->count(),
                    ServerClient::whereBetween('created_at', [Carbon::today()->subDays(4), Carbon::today()->subDays(3)])->count(),
                    ServerClient::whereBetween('created_at', [Carbon::today()->subDays(3), Carbon::today()->subDays(2)])->count(),
                    ServerClient::whereBetween('created_at', [Carbon::today()->subDays(2), Carbon::today()->subDays(1)])->count(),
                    ServerClient::whereBetween('created_at', [Carbon::today()->subDays(1), Carbon::today()])->count(),
                    ServerClient::whereBetween('created_at', [Carbon::today(), Carbon::today()->addDay()])->count(),
                ]),

            // Staff Stats
            Stat::make('👨‍💼 Active Staff', "{$activeStaff}/{$totalStaff}")
                ->description($activeStaff === $totalStaff ? 'All staff active' : ($totalStaff - $activeStaff) . ' inactive')
                ->descriptionIcon($activeStaff === $totalStaff ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($activeStaff === $totalStaff ? 'success' : 'warning'),
        ];
    }

    public function getColumns(): int
    {
        return 3;
    }

    protected function getPollingInterval(): ?string
    {
        return '30s'; // Refresh every 30 seconds
    }
}
