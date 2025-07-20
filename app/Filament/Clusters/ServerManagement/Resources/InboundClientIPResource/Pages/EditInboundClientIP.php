<?php

namespace App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInboundClientIP extends EditRecord
{
    protected static string $resource = InboundClientIPResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
