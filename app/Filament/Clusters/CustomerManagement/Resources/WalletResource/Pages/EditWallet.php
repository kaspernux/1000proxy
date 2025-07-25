<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\WalletResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
