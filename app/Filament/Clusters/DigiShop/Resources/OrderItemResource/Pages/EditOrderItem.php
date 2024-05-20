<?php

namespace App\Filament\Clusters\DigiShop\Resources\OrderItemResource\Pages;

use App\Filament\Clusters\DigiShop\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderItem extends EditRecord
{
    protected static string $resource = OrderItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
