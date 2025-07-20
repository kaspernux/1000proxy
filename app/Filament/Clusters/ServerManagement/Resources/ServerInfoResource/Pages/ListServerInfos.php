<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerInfos extends ListRecords
{
    protected static string $resource = ServerInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
