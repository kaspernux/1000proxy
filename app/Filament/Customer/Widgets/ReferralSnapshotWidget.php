<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;

class ReferralSnapshotWidget extends BaseWidget
{
    protected static ?int $sort = 4;

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

        // Placeholder logic (replace with real referral stats service when available)
        $totalReferrals = $customer->referrals()->count();
        $activeReferrals = $customer->referrals()->where('is_active', true)->count();
        $earnings = $totalReferrals * 2.5; // Assume $2.50 per referral (placeholder)

        $conversion = $totalReferrals > 0 ? round(($activeReferrals / $totalReferrals) * 100, 1) : 0;

        return [
            Stat::make('Referrals', $totalReferrals)
                ->icon('heroicon-o-user-group')
                ->description('Total joined')
                ->color('info')
                ->url(route('filament.customer.pages.referral-system')),

            Stat::make('Active Referrals', $activeReferrals)
                ->icon('heroicon-o-bolt')
                ->description($conversion . '% active')
                ->color($conversion >= 60 ? 'success' : ($conversion >= 30 ? 'warning' : 'danger')),

            Stat::make('Est. Earnings', '$' . number_format($earnings, 2))
                ->icon('heroicon-o-banknotes')
                ->description('Pending rewards')
                ->color($earnings > 0 ? 'success' : 'gray'),
        ];
    }
}
