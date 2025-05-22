<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderItem extends ViewRecord
{
    protected static string $resource = OrderItemResource::class;
    
}
