<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\GiftListResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\GiftListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGiftList extends EditRecord
{
    protected static string $resource = GiftListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
