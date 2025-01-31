<?php

namespace App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource\Pages;

use App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDownloadableItems extends ListRecords
{
    protected static string $resource = DownloadableItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
