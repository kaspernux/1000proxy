<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientTraffic extends ViewRecord
{
    protected static string $resource = ClientTrafficResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
