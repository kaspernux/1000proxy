<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerCategory extends ViewRecord
{
    protected static string $resource = ServerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
