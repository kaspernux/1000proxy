<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class OrderStats extends BaseWidget
{
    protected ?string $heading = '📦 Order Overview';
    protected ?string $description = 'Quick glance at your order pipeline and revenue';
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        return [
            Stat::make('New Orders', Order::where('order_status', 'new')->count())
                ->description('Awaiting fulfillment')
                ->descriptionIcon('heroicon-o-sparkles')
                ->color('info'),

            Stat::make('Processing', Order::where('order_status', 'processing')->count())
                ->description('In progress')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning'),

            Stat::make('Disputes', Order::where('order_status', 'dispute')->count())
                ->description('Needs attention')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Total Sales', Number::currency(Order::sum('grand_amount') ?? 0, 'USD'))
                ->description('All-time revenue')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
