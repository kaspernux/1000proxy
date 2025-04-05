<?php

namespace App\Filament\Clusters\ProxyShop\Resources\PaymentResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
