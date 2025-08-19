<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use BackedEnum;

class CustomerManagement extends Cluster
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user';
    protected static ?int $navigationSort = 1;

    public static function getLabel(): string
    {
        return 'Customer Management';
    }

    public static function getPluralLabel(): string
    {
        return 'Customer Management';
    }
}
