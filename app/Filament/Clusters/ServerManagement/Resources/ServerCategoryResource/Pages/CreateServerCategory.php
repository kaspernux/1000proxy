<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServerCategory extends CreateRecord
{
    protected static string $resource = ServerCategoryResource::class;
}
