<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerClient extends EditRecord
{
    protected static string $resource = ServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
