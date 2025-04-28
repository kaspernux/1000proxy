<?php

namespace App\Filament\Customer\Clusters;

use Filament\Clusters\Cluster;

class MySupport extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left-ellipsis';
    protected static ?string $navigationLabel = 'Support Tickets';
}
