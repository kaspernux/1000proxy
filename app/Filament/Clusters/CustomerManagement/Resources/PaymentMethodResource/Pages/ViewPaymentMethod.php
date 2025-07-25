<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\PaymentMethodResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\PaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentMethod extends ViewRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
