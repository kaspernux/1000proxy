<?php

namespace App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource\Pages;

use App\Filament\Customer\Clusters\MyTools\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    /**
     * Must match parent signature exactly:
     */
    public function mount(string|int|null $record = null): void
    {
        // Force-load the current customer’s ID instead:
        parent::mount(auth('customer')->id());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
