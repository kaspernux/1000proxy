<?php

namespace App\Filament\Clusters\Shop\Resources\ReviewResource\Pages;

use App\Filament\Clusters\Shop\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;
}