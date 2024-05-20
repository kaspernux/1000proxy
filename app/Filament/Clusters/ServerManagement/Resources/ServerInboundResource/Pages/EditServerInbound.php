<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerInbound extends EditRecord
{
    protected static string $resource = ServerInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
