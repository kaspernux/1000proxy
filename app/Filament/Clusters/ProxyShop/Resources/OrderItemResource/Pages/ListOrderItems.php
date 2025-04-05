<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrderItems extends ListRecords
{
    protected static string $resource = OrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
