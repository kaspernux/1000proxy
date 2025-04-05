<?php

namespace App\Filament\Clusters\ProxyShop\Resources\PaymentResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
