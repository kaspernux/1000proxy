<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerInfo extends EditRecord
{
    protected static string $resource = ServerInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
