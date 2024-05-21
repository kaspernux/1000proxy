<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
