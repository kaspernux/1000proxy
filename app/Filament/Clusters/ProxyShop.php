<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use BackedEnum;

class ProxyShop extends Cluster
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 2;
}
