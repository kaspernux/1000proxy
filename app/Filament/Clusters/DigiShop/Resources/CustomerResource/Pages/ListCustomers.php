<?php

namespace App\Filament\Clusters\DigiShop\Resources\CustomerResource\Pages;

use App\Filament\Clusters\DigiShop\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
