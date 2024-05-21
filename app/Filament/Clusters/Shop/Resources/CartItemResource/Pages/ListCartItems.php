<?php

namespace App\Filament\Clusters\Shop\Resources\CartItemResource\Pages;

use App\Filament\Clusters\Shop\Resources\CartItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCartItems extends ListRecords
{
    protected static string $resource = CartItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}