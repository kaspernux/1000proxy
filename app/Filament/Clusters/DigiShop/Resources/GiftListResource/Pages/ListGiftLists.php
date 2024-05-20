<?php

namespace App\Filament\Clusters\DigiShop\Resources\GiftListResource\Pages;

use App\Filament\Clusters\DigiShop\Resources\GiftListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGiftLists extends ListRecords
{
    protected static string $resource = GiftListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
