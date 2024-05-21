<?php

namespace App\Filament\Clusters\Shop\Resources\RatingResource\Pages;

use App\Filament\Clusters\Shop\Resources\RatingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRatings extends ListRecords
{
    protected static string $resource = RatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}