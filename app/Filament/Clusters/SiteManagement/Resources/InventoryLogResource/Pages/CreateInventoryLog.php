<?php

namespace App\Filament\Clusters\SiteManagement\Resources\InventoryLogResource\Pages;

use App\Filament\Clusters\SiteManagement\Resources\InventoryLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryLog extends CreateRecord
{
    protected static string $resource = InventoryLogResource::class;
}
