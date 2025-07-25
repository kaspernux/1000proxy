<?php

namespace App\Filament\Clusters\ServerManagement\Resources\EnhancedServerResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\EnhancedServerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEnhancedServer extends CreateRecord
{
    protected static string $resource = EnhancedServerResource::class;
}
