<?php

namespace App\Filament\Clusters\DigiShop\Resources\CustomerResource\Pages;

use App\Filament\Clusters\DigiShop\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
