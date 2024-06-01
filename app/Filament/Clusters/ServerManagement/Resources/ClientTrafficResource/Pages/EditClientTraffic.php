<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientTraffic extends EditRecord
{
    protected static string $resource = ClientTrafficResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
