<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}
