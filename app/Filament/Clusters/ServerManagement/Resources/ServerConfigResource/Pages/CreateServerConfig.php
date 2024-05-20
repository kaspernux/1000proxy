<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServerConfig extends CreateRecord
{
    protected static string $resource = ServerConfigResource::class;
}
