<?php

namespace App\Filament\Customer\Clusters;

use Filament\Clusters\Cluster;

class MyOrders extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'My Orders';
}
