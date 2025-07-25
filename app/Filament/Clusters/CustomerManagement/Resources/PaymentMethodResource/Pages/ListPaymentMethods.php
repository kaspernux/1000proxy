<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\PaymentMethodResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
