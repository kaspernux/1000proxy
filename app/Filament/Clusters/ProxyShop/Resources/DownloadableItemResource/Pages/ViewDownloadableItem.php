<?php

namespace App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDownloadableItem extends ViewRecord
{
    protected static string $resource = DownloadableItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
