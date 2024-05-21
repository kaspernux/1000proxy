<?php

namespace App\Filament\Clusters\ProxyShop\Resources\PaymentsResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\PaymentsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayments extends ViewRecord
{
    protected static string $resource = PaymentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
