<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerPlan extends ViewRecord
{
    protected static string $resource = ServerPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
