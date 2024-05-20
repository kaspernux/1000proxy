<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerPlans extends ListRecords
{
    protected static string $resource = ServerPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
