<?php

namespace App\Filament\Clusters\SiteManagement\Resources\SiteSettingResource\Pages;

use App\Filament\Clusters\SiteManagement\Resources\SiteSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteSetting extends CreateRecord
{
    protected static string $resource = SiteSettingResource::class;
}
