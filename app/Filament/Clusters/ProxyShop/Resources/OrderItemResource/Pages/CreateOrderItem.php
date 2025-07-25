<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderItemResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderItem extends CreateRecord
{
    protected static string $resource = OrderItemResource::class;
}
