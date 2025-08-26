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

        $totalReferrals = Customer::query()->where('refered_by', $customer->id)->count();
        $activeReferrals = \App\Models\Order::query()
            ->whereIn('customer_id', function($q) use ($customer) { $q->select('id')->from('customers')->where('refered_by', $customer->id); })
            ->where('payment_status', 'paid')
            ->distinct('customer_id')
            ->count('customer_id');
        $earnings = (float) \App\Models\WalletTransaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'credit')
            ->where('metadata->referral', true)
            ->sum('amount');

        $conversion = $totalReferrals > 0 ? round(($activeReferrals / max(1,$totalReferrals)) * 100, 1) : 0;

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

            Stat::make('Referral Earnings', '$' . number_format($earnings, 2))
                ->icon('heroicon-o-banknotes')
                ->description('Wallet credits')
                ->color($earnings > 0 ? 'success' : 'gray'),
        ];
    }
}
