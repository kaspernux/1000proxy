<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use BackedEnum;

class ServerManagement extends Cluster
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-server-stack';
    protected static ?int $navigationSort = 3;
}