<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ServerClient;
use App\Models\ServerReview;
use App\Models\ServerRating;
use App\Models\DownloadableItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class AdminStatsOverview extends BaseWidget
{
    protected ?string $heading = '🚀 Admin Overview';
    protected ?string $description = 'Key metrics: users, services, orders, revenue & more';
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        return [
            Stat::make('Customers', Customer::count())
                ->description('Active users')
                ->descriptionIcon('heroicon-s-users')
                ->color('primary'),

            Stat::make('Services', ServerClient::count())
                ->description('Active proxies')
                ->descriptionIcon('heroicon-s-server')
                ->color('info'),

            Stat::make('Total Orders', Order::count())
                ->description('All orders placed')
                ->descriptionIcon('heroicon-s-shopping-cart')
                ->color('success'),

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

            Stat::make('Revenue', Number::currency(Order::sum('grand_amount') ?? 0, 'USD'))
                ->description('All-time revenue')
                ->descriptionIcon('heroicon-s-currency-dollar')
                ->color('success'),

            Stat::make('Downloads', DownloadableItem::count())
                ->description('Available files')
                ->descriptionIcon('heroicon-s-folder-arrow-down')
                ->color('gray'),

            Stat::make('Reviews', ServerReview::count())
                ->description('Submitted feedback')
                ->descriptionIcon('heroicon-s-chat-bubble-left-ellipsis')
                ->color('warning'),

            Stat::make('Avg. Rating', round(ServerRating::avg('rating') ?? 0, 2))
                ->description('⭐ stars')
                ->descriptionIcon('heroicon-s-star')
                ->color('warning'),
        ];
    }
}
