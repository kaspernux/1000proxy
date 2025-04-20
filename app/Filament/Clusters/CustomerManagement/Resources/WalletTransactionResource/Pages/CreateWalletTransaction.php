<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\WalletTransactionResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\WalletTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWalletTransaction extends CreateRecord
{
    protected static string $resource = WalletTransactionResource::class;
}
