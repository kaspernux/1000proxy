<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerClientResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerClientResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListServerClients extends ListRecords
{
    protected static string $resource = ServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for customers - clients are created through orders
        ];
    }

    public function getTitle(): string
    {
        return 'My Proxy Clients';
    }

    public function getHeading(): string
    {
        return 'My Proxy Clients';
    }

    public function getSubheading(): string
    {
        return 'View and manage your proxy client configurations';
    }
}
