<?php

namespace App\Filament\Clusters\DigiShop\Resources\PaymentMethodResource\Pages;

use App\Filament\Clusters\DigiShop\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;
}
