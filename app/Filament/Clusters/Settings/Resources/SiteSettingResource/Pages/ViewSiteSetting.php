<?php

namespace App\Filament\Clusters\Settings\Resources\SiteSettingResource\Pages;

use App\Filament\Clusters\Settings\Resources\SiteSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSiteSetting extends ViewRecord
{
    protected static string $resource = SiteSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
