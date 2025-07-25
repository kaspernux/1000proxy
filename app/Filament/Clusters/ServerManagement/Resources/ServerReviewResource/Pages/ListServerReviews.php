<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerReviewResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerReviews extends ListRecords
{
    protected static string $resource = ServerReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
