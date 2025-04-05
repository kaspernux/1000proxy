<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\GiftListResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\GiftListResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGiftList extends ViewRecord
{
    protected static string $resource = GiftListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
