<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource;
use App\Http\Controllers\ServerClientController;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class EditServerClient extends EditRecord
{
    protected static string $resource = ServerClientResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $controller = App::make(ServerClientController::class);
        $request = new Request($data);
        $response = $controller->update($request, $record->id);
        return ServerClient::findOrFail($response->getData()->serverClient->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}