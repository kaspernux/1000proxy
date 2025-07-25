<?php

namespace App\Filament\Customer\Clusters\MyWallet\Resources\WalletTransactionResource\Pages;

use App\Filament\Customer\Clusters\MyWallet\Resources\WalletTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWalletTransaction extends CreateRecord
{
    protected static string $resource = WalletTransactionResource::class;
}
