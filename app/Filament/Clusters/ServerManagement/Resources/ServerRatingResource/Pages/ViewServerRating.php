<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerRating extends ViewRecord
{
    protected static string $resource = ServerRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
