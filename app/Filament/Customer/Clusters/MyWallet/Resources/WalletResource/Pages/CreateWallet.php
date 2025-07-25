<?php

namespace App\Filament\Customer\Clusters\MyWallet\Resources\WalletResource\Pages;

use App\Filament\Customer\Clusters\MyWallet\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;
}
