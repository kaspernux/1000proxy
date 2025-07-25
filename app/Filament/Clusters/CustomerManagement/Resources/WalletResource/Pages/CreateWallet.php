<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\WalletResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;
}
