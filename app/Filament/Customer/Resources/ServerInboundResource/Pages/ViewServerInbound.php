<?php

namespace App\Filament\Customer\Resources\ServerInboundResource\Pages;

use App\Filament\Customer\Resources\ServerInboundResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerInbound extends ViewRecord
{
    protected static string $resource = ServerInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
