<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class StaffManagement extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Staff Management';
    protected static ?string $slug = 'staff-management';
    protected static ?int $navigationSort = 10;
}
