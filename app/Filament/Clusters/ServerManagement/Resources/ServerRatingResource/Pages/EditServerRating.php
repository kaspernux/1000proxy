<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerRatingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerRating extends EditRecord
{
    protected static string $resource = ServerRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
