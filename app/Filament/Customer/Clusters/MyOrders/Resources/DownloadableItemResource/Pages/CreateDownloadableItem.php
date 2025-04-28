<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource\Pages;

use App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDownloadableItem extends CreateRecord
{
    protected static string $resource = DownloadableItemResource::class;
}
