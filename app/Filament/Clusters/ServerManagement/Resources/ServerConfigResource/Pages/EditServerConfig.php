<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerConfig extends EditRecord
{
    protected static string $resource = ServerConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
