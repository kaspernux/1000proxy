<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\ServerClient;

class CustomerStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::guard('customer')->check();
    }

    protected function getStats(): array
    {
        $customer   = Auth::guard('customer')->user();
        $balance    = optional($customer?->wallet)->balance ?? 0;
        $orders     = $customer?->orders()->count() ?? 0;
        $customerId = Auth::guard('customer')->id();

        $services = ServerClient::query()
            ->where('email', 'LIKE', "%#ID {$customerId}")
            ->where('enable', true)
            ->count();

        return [
            Stat::make('Wallet Balance', '$' . number_format($balance, 2))
                ->icon('heroicon-o-banknotes')
                ->description('Top-up via BTC, XMR, SOL')
                ->color($balance > 0 ? 'success' : 'danger')
                ->url(route('filament.customer.pages.wallet-management')),

            Stat::make('My Orders', $orders)
                ->icon('heroicon-o-receipt-refund')
                ->description('Total orders placed')
                ->color('info')
                ->url(route('filament.customer.pages.order-management')),

            Stat::make('Active Services', $services)
                ->icon('heroicon-o-server-stack')
                ->description('Active subscriptions')
                ->color('primary')
                ->url(route('filament.customer.pages.my-active-servers')),
        ];
    }
}
