<?php

namespace App\Filament\Customer\Clusters\MySupport\Resources\ServerReviewResource\Pages;

use App\Filament\Customer\Clusters\MySupport\Resources\ServerReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerReview extends EditRecord
{
    protected static string $resource = ServerReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
