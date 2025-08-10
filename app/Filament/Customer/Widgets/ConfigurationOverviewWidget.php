<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\ServerClient;

class ConfigurationOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return Auth::guard('customer')->check();
    }

    protected function getStats(): array
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return [];
        }

        $clients = ServerClient::where('customer_id', $customer->id)->get();
        $total = $clients->count();
        $active = $clients->where('enable', true)->count();
        $suspended = $clients->where('status', 'suspended')->count();

        $healthPercent = $total > 0 ? round(($active / $total) * 100, 1) : 0;

        return [
            Stat::make('Configs', $total)
                ->icon('heroicon-o-cog-6-tooth')
                ->description('Total generated')
                ->color('primary')
                ->url(route('filament.customer.pages.configuration-guides')),

            Stat::make('Active', $active)
                ->icon('heroicon-o-check-circle')
                ->description($healthPercent . '% healthy')
                ->color($healthPercent >= 80 ? 'success' : ($healthPercent >= 50 ? 'warning' : 'danger')),

            Stat::make('Issues', $suspended)
                ->icon('heroicon-o-exclamation-triangle')
                ->description('Require attention')
                ->color($suspended > 0 ? 'danger' : 'success'),
        ];
    }
}
