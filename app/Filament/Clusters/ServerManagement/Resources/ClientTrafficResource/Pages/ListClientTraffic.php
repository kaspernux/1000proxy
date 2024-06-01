<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientTraffic extends ListRecords
{
    protected static string $resource = ClientTrafficResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
