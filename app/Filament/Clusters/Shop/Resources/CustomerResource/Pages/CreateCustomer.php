<?php

namespace App\Filament\Clusters\Shop\Resources\CustomerResource\Pages;

use App\Filament\Clusters\Shop\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}