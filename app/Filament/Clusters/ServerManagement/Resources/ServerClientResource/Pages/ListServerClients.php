<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerClients extends ListRecords
{
    protected static string $resource = ServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
