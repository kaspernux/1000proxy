<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerPlan extends EditRecord
{
    protected static string $resource = ServerPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
