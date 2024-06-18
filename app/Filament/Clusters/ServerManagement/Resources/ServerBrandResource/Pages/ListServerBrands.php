<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerBrands extends ListRecords
{
    protected static string $resource = ServerBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
