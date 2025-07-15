<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerResource;
use Filament\Resources\Pages\ListRecords;

class ListServers extends ListRecords
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for customers
        ];
    }

    public function getTitle(): string
    {
        return 'My Servers';
    }

    public function getHeading(): string
    {
        return 'My Servers';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Can add server statistics widgets here later
        ];
    }
}
