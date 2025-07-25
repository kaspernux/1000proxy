<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderItem extends ViewRecord
{
    protected static string $resource = OrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
