<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerTagResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerTagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerTags extends ListRecords
{
    protected static string $resource = ServerTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
