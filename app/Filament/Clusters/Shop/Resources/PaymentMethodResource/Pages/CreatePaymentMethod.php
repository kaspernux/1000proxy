<?php

namespace App\Filament\Clusters\Shop\Resources\PaymentMethodResource\Pages;

use App\Filament\Clusters\Shop\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;
}