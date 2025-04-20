<?php

namespace App\Filament\Customer\Resources\CustomerServerClientResource\Pages;

use App\Filament\Customer\Resources\CustomerServerClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerServerClients extends ListRecords
{
    protected static string $resource = CustomerServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
