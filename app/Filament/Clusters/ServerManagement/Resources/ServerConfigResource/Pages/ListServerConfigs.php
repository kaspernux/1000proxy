<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerConfigs extends ListRecords
{
    protected static string $resource = ServerConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
