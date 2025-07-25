<?php

namespace App\Filament\Customer\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;

class MyOrders extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'My Orders';
    protected static ?string $navigationGroup = 'Orders & Services';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $customerId = Auth::guard('customer')->id();
        if (!$customerId) {
            return null;
        }

        $pendingCount = Order::where('customer_id', $customerId)
            ->where('order_status', 'processing')
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
