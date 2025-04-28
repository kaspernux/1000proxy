<?php

namespace App\Filament\Customer\Clusters\MyWallet\Resources\WalletTransactionResource\Pages;

use App\Filament\Customer\Clusters\MyWallet\Resources\WalletTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWalletTransaction extends ViewRecord
{
    protected static string $resource = WalletTransactionResource::class;
}
