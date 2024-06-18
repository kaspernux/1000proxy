<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerBrand extends ViewRecord
{
    protected static string $resource = ServerBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
