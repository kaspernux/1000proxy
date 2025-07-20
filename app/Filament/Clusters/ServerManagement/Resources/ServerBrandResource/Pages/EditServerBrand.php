<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerBrand extends EditRecord
{
    protected static string $resource = ServerBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
