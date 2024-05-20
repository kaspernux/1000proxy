<?php

namespace App\Filament\Clusters\SiteManagement\Resources\SiteSettingResource\Pages;

use App\Filament\Clusters\SiteManagement\Resources\SiteSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteSettings extends ListRecords
{
    protected static string $resource = SiteSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
