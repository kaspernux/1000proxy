<?php

namespace App\Filament\Customer\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Support\Facades\Auth;
use App\Models\ServerClient;
use App\Models\Subscription;

class MyServices extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'My Services';

    protected static ?string $slug = 'my-services';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Services';

    /**
     * Get navigation badge for active services count
     */
    public static function getNavigationBadge(): ?string
    {
        $customerId = Auth::guard('customer')->id();

        if (!$customerId) {
            return null;
        }

        $activeServicesCount = ServerClient::where('customer_id', $customerId)
            ->where('status', 'active')
            ->count();

        return $activeServicesCount > 0 ? (string) $activeServicesCount : null;
    }

    /**
     * Get navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
