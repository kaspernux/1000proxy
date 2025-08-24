<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Widgets; 
use Filament\Resources\Components\Tab;

class ViewServer extends ViewRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Clusters\ServerManagement\Resources\ServerResource\Widgets\ServerStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Clusters\ServerManagement\Resources\ServerResource\Widgets\ServerMiniCharts::class,
        ];
    }
}
