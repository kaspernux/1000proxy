<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerInfoResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerInfos extends ListRecords
{
    protected static string $resource = ServerInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
