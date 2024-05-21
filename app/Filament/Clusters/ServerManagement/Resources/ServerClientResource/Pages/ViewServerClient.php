<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerClient extends ViewRecord
{
    protected static string $resource = ServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
