<?php

namespace App\Filament\Customer\Clusters\MyWallet\Resources\WalletTransactionResource\Pages;

use App\Filament\Customer\Clusters\MyWallet\Resources\WalletTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWalletTransactions extends ListRecords
{
    protected static string $resource = WalletTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
