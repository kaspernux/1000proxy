<?php

namespace App\Filament\Clusters\Notifications;

use Filament\Clusters\Cluster;
use BackedEnum;

class Notifications extends Cluster
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Notifications';
    protected static ?int $navigationSort = 90;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin','support_manager']);
    }
}
