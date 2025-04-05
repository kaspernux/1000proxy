<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class OrderStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('New Orders', Order::query()->where('order_status','new')->count()),
            Stat::make('Processing', Order::query()->where('order_status','processing')->count()),
            // Stat::make('Completed', Order::query()->where('order_status','completed')->count()),
            Stat::make('Disputes', Order::query()->where('order_status','dispute')->count()),
            Stat::make('Total Sales', Number::currency(Order::query()->sum('grand_amount') ?? 0, 'USD' ))
        ];
    }
}