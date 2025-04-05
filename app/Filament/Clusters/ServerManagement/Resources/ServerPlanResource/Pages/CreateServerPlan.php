<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServerPlan extends CreateRecord
{
    protected static string $resource = ServerPlanResource::class;
}
