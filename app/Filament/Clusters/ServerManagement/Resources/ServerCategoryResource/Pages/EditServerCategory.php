<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerCategory extends EditRecord
{
    protected static string $resource = ServerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
