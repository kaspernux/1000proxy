<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerInboundResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerInboundResource;
use Filament\Resources\Pages\ListRecords;

class ListServerInbounds extends ListRecords
{
    protected static string $resource = ServerInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for customers
        ];
    }

    public function getTitle(): string
    {
        return 'My Inbounds';
    }

    public function getHeading(): string
    {
        return 'My Inbounds';
    }
}
