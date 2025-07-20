<?php

namespace App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDownloadableItem extends EditRecord
{
    protected static string $resource = DownloadableItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
