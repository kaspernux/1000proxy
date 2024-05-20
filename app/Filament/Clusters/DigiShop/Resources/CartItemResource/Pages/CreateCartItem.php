<?php

namespace App\Filament\Clusters\DigiShop\Resources\CartItemResource\Pages;

use App\Filament\Clusters\DigiShop\Resources\CartItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCartItem extends CreateRecord
{
    protected static string $resource = CartItemResource::class;
}
