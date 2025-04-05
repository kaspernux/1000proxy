<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;

use Exception;
use App\Models\Server;
use App\Models\ServerClient;
use App\Services\XUIService;
use Illuminate\Http\Request;
use App\Models\ServerInbound;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource;

class CreateServerInbound extends CreateRecord
{
    protected static string $resource = ServerInboundResource::class;

    protected function handleRecordCreation(array $data): ServerInbound
    {
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

        $xuiService = new XUIService($data['server_id']);
        $request = new Request($data);
        $inboundResponse = $xuiService->addInbound($request);

        Log::info("Inbound Response: ", [$inboundResponse]);

        if (!is_array($inboundResponse) || !array_key_exists('obj', $inboundResponse)) {
            throw new Exception("Invalid response structure from addInbound method.");
        }

        $data['remark'] = $inboundResponse['obj']['remark'] ?? '';
        $data['listen'] = $inboundResponse['obj']['listen'] ?? '';
        $data['port'] = $inboundResponse['obj']['port'] ?? '';
        $data['protocol'] = $inboundResponse['obj']['protocol'] ?? '';
        $data['settings'] = $inboundResponse['obj']['settings'] ?? '';
        $data['streamSettings'] = $inboundResponse['obj']['streamSettings'] ?? '';
        $data['sniffing'] = $inboundResponse['obj']['sniffing'] ?? '';
        $data['enable'] = $inboundResponse['obj']['enable'] ?? '';

        if (!empty($inboundResponse['obj']['expiryTime']) && strtotime($inboundResponse['obj']['expiryTime']) !== false) {
            $data['expiryTime'] = date('Y-m-d H:i:s', strtotime($inboundResponse['obj']['expiryTime']));
        } else {
            $data['expiryTime'] = null;
        }

        $serverInbound = ServerInbound::create($data);

        $clientData = $inboundResponse['client'] ?? [];
        $clientData['server_inbound_id'] = $serverInbound->id;

        if (!empty($clientData)) {
            ServerClient::create($clientData);
        }

        return $serverInbound;
    }
}
