<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class CustomerManagement extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
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
