<?php

namespace App\Filament\Customer\Resources\ServerInboundResource\Pages;

use App\Filament\Customer\Resources\ServerInboundResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerInbounds extends ListRecords
{
    protected static string $resource = ServerInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
