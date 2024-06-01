<?php

namespace App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInboundClientIP extends CreateRecord
{
    protected static string $resource = InboundClientIPResource::class;
}
