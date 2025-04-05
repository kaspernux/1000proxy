<?php

namespace App\Filament\Clusters\ProxyShop\Resources\PaymentResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
