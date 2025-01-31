<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerReviewResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerReview extends EditRecord
{
    protected static string $resource = ServerReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
