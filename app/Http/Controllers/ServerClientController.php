<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerClient;
use App\Models\ServerInbound;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class ServerClientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'server_inbound_id' => 'required|exists:server_inbounds,id',
            'password' => 'required|string',
            'flow' => 'nullable|string',
            'limitIp' => 'nullable|integer',
            'totalGb' => 'nullable|integer',
            'expiryTime' => 'nullable|integer',
            'tgId' => 'nullable|string',
            'subId' => 'nullable|string',
            'enable' => 'required|boolean',
            'reset' => 'nullable|integer',
            'qr_code_sub' => 'nullable|string',
            'qr_code_sub_json' => 'nullable|string',
            'qr_code_client' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Generate a UUID for the email parameter
            $uuidEmail = Uuid::uuid4()->toString();

            $inbound = ServerInbound::findOrFail($request->server_inbound_id);
            $server = $inbound->server;
            $xuiService = new XUIService($server);

            // Prepare data for the remote server
            $data = $request->all();
            $data['email'] = $uuidEmail;

            // Synchronize with XUI server
            $xuiResponse = $xuiService->createClient($data);

            if (isset($xuiResponse['error'])) {
                DB::rollBack();
                return response()->json($xuiResponse, 400);
            }

            $clientId = $xuiResponse['clientId']; // Assuming 'clientId' is returned from the XUI server

            // Create local ServerClient record
            $client = ServerClient::create(array_merge($data, ['clientId' => $clientId]));

            DB::commit();
            return response()->json($client, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getClient(Request $request, $clientId)
    {
        $server = Server::findOrFail($request->server_id);
        $xuiService = new XUIService($server);

        $client = $xuiService->getClient($clientId);
        return response()->json($client);
    }

    public function update(Request $request, $clientId)
    {
        $request->validate([
            'server_inbound_id' => 'required|exists:server_inbounds,id',
            'password' => 'nullable|string',
            'flow' => 'nullable|string',
            'limitIp' => 'nullable|integer',
            'totalGb' => 'nullable|integer',
            'expiryTime' => 'nullable|integer',
            'tgId' => 'nullable|string',
            'subId' => 'nullable|string',
            'enable' => 'nullable|boolean',
            'reset' => 'nullable|integer',
            'qr_code_sub' => 'nullable|string',
            'qr_code_sub_json' => 'nullable|string',
            'qr_code_client' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $inbound = ServerInbound::findOrFail($request->server_inbound_id);
            $server = $inbound->server;
            $xuiService = new XUIService($server);

            $data = $request->all();
            $xuiResponse = $xuiService->updateClient($clientId, $data);

            if (isset($xuiResponse['error'])) {
                DB::rollBack();
                return response()->json($xuiResponse, 400);
            }

            $client = ServerClient::where('clientId', $clientId)->firstOrFail();
            $client->update($data);

            DB::commit();
            return response()->json($client, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request, $clientId)
    {
        DB::beginTransaction();

        try {
            $inbound = ServerInbound::findOrFail($request->server_inbound_id);
            $server = $inbound->server;
            $xuiService = new XUIService($server);

            $xuiResponse = $xuiService->deleteClient($clientId);

            if (isset($xuiResponse['error'])) {
                DB::rollBack();
                return response()->json($xuiResponse, 400);
            }

            $client = ServerClient::where('clientId', $clientId)->firstOrFail();
            $client->delete();

            DB::commit();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resetTraffic(Request $request, $clientId)
    {
        $server = Server::findOrFail($request->server_id);
        $xuiService = new XUIService($server);

        $response = $xuiService->resetClientTraffic($clientId);
        return response()->json($response);
    }
}
