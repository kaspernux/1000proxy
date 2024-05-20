<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;
}
