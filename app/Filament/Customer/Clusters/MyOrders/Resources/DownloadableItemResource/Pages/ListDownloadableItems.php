<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDownloadableItems extends ListRecords
{
    protected static string $resource = DownloadableItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
