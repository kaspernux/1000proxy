<?php

namespace App\Filament\Clusters\DigiShop\Resources\OrderItemResource\Pages;

use App\Filament\Clusters\DigiShop\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderItem extends CreateRecord
{
    protected static string $resource = OrderItemResource::class;
}
