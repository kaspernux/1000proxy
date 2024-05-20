<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServerInfo extends CreateRecord
{
    protected static string $resource = ServerInfoResource::class;
}
