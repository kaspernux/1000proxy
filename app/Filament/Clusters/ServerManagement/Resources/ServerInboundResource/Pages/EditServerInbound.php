<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Http\Controllers\ServerInboundController;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EditServerInbound extends EditRecord
{
    protected static string $resource = ServerInboundResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Create a Request instance from the data array
        $request = new Request($data);

        // Use the ServerInboundController to handle the update logic
        $serverInboundController = App::make(ServerInboundController::class);
        $response = $serverInboundController->update($record->id, $request);

        // Return the updated model instance
        return ServerInbound::findOrFail($response->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}