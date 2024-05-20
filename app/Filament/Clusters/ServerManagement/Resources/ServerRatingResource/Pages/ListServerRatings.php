<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerRatings extends ListRecords
{
    protected static string $resource = ServerRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
