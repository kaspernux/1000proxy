<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure password is set to satisfy DB constraints; model will hash via cast
        if (empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Str::password(12);
        }
        return $data;
    }
}
