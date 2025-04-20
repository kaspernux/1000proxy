<?php

namespace App\Filament\Customer\Resources\CustomerServerClientResource\Pages;

use App\Filament\Customer\Resources\CustomerServerClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerServerClient extends ViewRecord
{
    protected static string $resource = CustomerServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
