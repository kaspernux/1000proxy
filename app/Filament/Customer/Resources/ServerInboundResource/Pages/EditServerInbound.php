<?php

namespace App\Filament\Customer\Resources\ServerInboundResource\Pages;

use App\Filament\Customer\Resources\ServerInboundResource;
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
