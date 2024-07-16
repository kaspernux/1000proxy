<?php

namespace App\Http\Controllers;

use App\Models\ServerClient;
use App\Models\ServerInbound;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
class ServerClientController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $clients = ServerClient::all();
            return response()->json($clients);
        } catch (\Exception $e) {
            Log::error('Error fetching server clients: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server clients'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $client = ServerClient::findOrFail($id);
            return response()->json($client);
        } catch (\Exception $e) {
            Log::error('Error fetching server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server client'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Extract server_id from request
            $server_id = $request->input('server_id');
            
            // Get server inbound
            $serverInbound = ServerInbound::findOrFail($request->input('server_inbound_id'));
            
            // Create client on remote server using XUIService
            $xuiService = new XUIService($server_id);
            $clientResponse = $xuiService->addClientInbound($request->all());
            
            // Save client data to the local database
            $clientData = [
                'server_inbound_id' => $serverInbound->id,
                'email' => $clientResponse['email'],
                'password' => $request->input('password'), // Ensure this is included in the form
                'flow' => $clientResponse['flow'] ?? 'None',
                'limitIp' => $clientResponse['limitIp'],
                'totalGB' => $clientResponse['totalGB'],
                'expiryTime' => $clientResponse['expiryTime'],
                'tgId' => $clientResponse['tgId'] ?? null,
                'subId' => $clientResponse['subId'],
                'enable' => $clientResponse['enable'],
                'reset' => $clientResponse['reset'] ?? null,
                'qr_code_sub' => $clientResponse['qr_code_sub'] ?? null,
                'qr_code_sub_json' => $clientResponse['qr_code_sub_json'] ?? null,
                'qr_code_client' => $clientResponse['qr_code_client'] ?? null,
            ];
            
            $serverClient = ServerClient::create($clientData);
            
            return response()->json($serverClient, 201);
        } catch (\Exception $e) {
            Log::error('Error adding server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add server client'], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $serverClient = ServerClient::findOrFail($id);
            $server_id = $serverClient->inbound->server_id;

            // Update client on remote server using XUIService
            $xuiService = new XUIService($server_id);
            $clientResponse = $xuiService->updateClient($request->all());

            // Update client data in the local database
            $serverClient->update($clientResponse['client']);

            return response()->json($serverClient);
        } catch (\Exception $e) {
            Log::error('Error updating server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server client'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $serverClient = ServerClient::findOrFail($id);
            $server_id = $serverClient->inbound->server_id;

            // Delete client on remote server using XUIService
            $xuiService = new XUIService($server_id);
            $xuiService->deleteClient($serverClient->id);

            // Delete client data from the local database
            $serverClient->delete();

            return response()->json(['message' => 'Server client deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete server client'], 500);
        }
    }
}