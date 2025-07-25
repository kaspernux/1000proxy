<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerTagResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerTagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerTag extends EditRecord
{
    protected static string $resource = ServerTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
