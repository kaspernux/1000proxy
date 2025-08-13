<?php

namespace App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function authorizeAccess(): void
    {
        // Always forbid manual creation; customers create orders via checkout.
        abort(403, 'Order creation is restricted to customer checkout flow.');
    }
}
