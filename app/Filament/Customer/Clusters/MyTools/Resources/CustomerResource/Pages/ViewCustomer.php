<?php

namespace App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource\Pages;

use App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    // Match parent signature, but allow null so PHP is happy:
    public function mount(string|int|null $record = null): void
    {
        parent::mount(auth('customer')->id());
    }
}
