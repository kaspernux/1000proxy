<?php

namespace App\Filament\Customer\Resources\CustomerServerClientResource\Pages;

use App\Filament\Customer\Resources\CustomerServerClientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerServerClient extends EditRecord
{
    protected static string $resource = CustomerServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
