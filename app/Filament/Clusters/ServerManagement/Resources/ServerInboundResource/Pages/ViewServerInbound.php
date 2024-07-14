<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Http\Controllers\ServerInboundController;
use Illuminate\Support\Facades\App;

class ViewServerInbound extends ViewRecord
{
    protected static string $resource = ServerInboundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function handleRecordRetrieval($key)
    {
        return app(ServerInboundController::class)->show($key);
    }
}