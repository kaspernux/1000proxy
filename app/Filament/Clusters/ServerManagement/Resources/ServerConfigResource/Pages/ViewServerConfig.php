<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerConfig extends ViewRecord
{
    protected static string $resource = ServerConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
