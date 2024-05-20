<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServerInbound extends CreateRecord
{
    protected static string $resource = ServerInboundResource::class;
}
