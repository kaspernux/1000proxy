<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource;
use App\Http\Controllers\ServerClientController;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateServerClient extends CreateRecord
{
    protected static string $resource = ServerClientResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $controller = App::make(ServerClientController::class);
        $request = new Request($data);
        $response = $controller->store($request);
        return ServerClient::findOrFail($response->getData()->serverClient->id);
    }
}

