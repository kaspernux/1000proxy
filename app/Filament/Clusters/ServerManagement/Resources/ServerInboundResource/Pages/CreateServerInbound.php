<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;

use Exception;
use App\Models\Server;
use App\Models\ServerInbound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Http\Controllers\ServerInboundController;
use App\Services\XUIService;
use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource;

class CreateServerInbound extends CreateRecord
{
    protected static string $resource = ServerInboundResource::class;

    protected function handleRecordCreation(array $data): ServerInbound
    {
        // Ensure server exists before creating server inbound
        $server = Server::findOrFail($data['server_id']);

        if (empty($server->panel_url) || empty($server->username) || empty($server->password)) {
            Log::error("Server configuration missing for server ID: " . $server->id);
            throw new Exception("Server configuration missing for server ID: " . $server->id);
        }

        Log::info("Server configuration found for server ID: " . $server->id, [
            'panel_url' => $server->panel_url,
            'username' => $server->username,
            'password' => $server->password,
        ]);

        // Create a Request instance from the data array
        $request = new Request($data);

        // Manually resolve the XUIService with the server_id
        $xuiService = new XUIService($data['server_id']);

        // Use the ServerInboundController to handle the creation logic
        $serverInboundController = new ServerInboundController($xuiService);
        $response = $serverInboundController->store($request);

        // Return the newly created model instance
        return ServerInbound::findOrFail($response->id);
    }
}
