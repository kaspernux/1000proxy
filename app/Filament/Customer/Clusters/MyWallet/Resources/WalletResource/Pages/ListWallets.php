<?php

namespace App\Filament\Customer\Clusters\MyWallet\Resources\WalletResource\Pages;

use App\Filament\Customer\Clusters\MyWallet\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
