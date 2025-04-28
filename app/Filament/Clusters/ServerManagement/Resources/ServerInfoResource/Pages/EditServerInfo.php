<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;


class EditServerInfo extends EditRecord
{
    protected static string $resource = ServerInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
