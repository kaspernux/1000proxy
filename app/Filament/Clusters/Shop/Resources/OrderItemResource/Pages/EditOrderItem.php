<?php

namespace App\Filament\Clusters\Shop\Resources\OrderItemResource\Pages;

use App\Filament\Clusters\Shop\Resources\OrderItemResource;
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