<?php

namespace App\Filament\Clusters\Shop\Resources\GiftListResource\Pages;

use App\Filament\Clusters\Shop\Resources\GiftListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGiftList extends EditRecord
{
    protected static string $resource = GiftListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}