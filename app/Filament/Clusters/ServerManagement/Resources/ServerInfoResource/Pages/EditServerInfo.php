<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\Pages;

use Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource;

class EditServerInfo extends EditRecord
{
    protected static string $resource = ServerInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}