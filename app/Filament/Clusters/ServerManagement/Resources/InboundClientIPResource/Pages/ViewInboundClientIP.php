<?php

namespace App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInboundClientIP extends ViewRecord
{
    protected static string $resource = InboundClientIPResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
