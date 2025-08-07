<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\ServerReview;
use App\Models\ServerRating;

class SupportOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return Auth::guard('customer')->check();
    }

    protected function getStats(): array
    {
        $customerId = Auth::guard('customer')->id();
        $reviews    = ServerReview::where('customer_id', $customerId)->count();
        $ratings    = ServerRating::where('customer_id', $customerId)->count();
        $average    = ServerRating::where('customer_id', $customerId)->avg('rating') ?? 0;
        $avgFmt     = number_format($average, 1);

        return [
            Stat::make('Reviews', $reviews)
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->description('Feedback submitted')
                ->color('info'),

            Stat::make('Ratings', $ratings)
                ->icon('heroicon-o-star')
                ->description('Servers rated')
                ->color('success'),

            Stat::make('Avg Rating', "⭐ {$avgFmt}")
                ->icon('heroicon-o-adjustments-horizontal')
                ->description('Average score')
                ->color($average >= 4 ? 'success' : ($average >= 2 ? 'warning' : 'danger')),
        ];
    }
}
