<?php

namespace App\Http\Controllers;

use App\Models\ServerInbound;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServerInboundController extends Controller
{
    protected $xuiService;

    public function __construct(XUIService $xuiService)
    {
        $this->xuiService = $xuiService;
    }

    // Retrieve a server inbound
    public function show(ServerInbound $serverInbound)
    {
        return response()->json($serverInbound);
    }

    // Update a server inbound
    public function update(Request $request, ServerInbound $serverInbound)
    {
        // Validate incoming request data here if needed

        // Update Server Inbound
        $serverInbound->update($request->all());

        return response()->json($serverInbound);
    }

    // Delete a server inbound
    public function destroy(ServerInbound $serverInbound)
    {
        // Delete Server Inbound
        $serverInbound->delete();

        return response()->json(['message' => 'Server inbound deleted successfully']);
    }

    // Add a new server inbound
    public function store(Request $request)
    {
        // Validate incoming request data here if needed

        // Create Server Inbound
        $serverInbound = ServerInbound::create($request->all());

        return response()->json($serverInbound, 201);
    }

    // List all server inbounds
    public function index()
    {
        $serverInbounds = ServerInbound::all();
        return response()->json($serverInbounds);
    }

    // Example method to manage server inbounds
    public function manageServerInbounds()
    {
        try {
            // Example: Fetch server inbounds from remote XUI
            $remoteServerInbounds = $this->xuiService->fetchServerInbounds();

            // Example: Process server inbounds
            foreach ($remoteServerInbounds as $remoteServerInbound) {
                // Process each remote server inbound as needed
                Log::info('Processing server inbound: ' . $remoteServerInbound['uuid']);
                // Example: Add server inbound to local database if needed
                ServerInbound::create([
                    'uuid' => $remoteServerInbound['uuid'],
                    'server_id' => $remoteServerInbound['server_id'],
                    // Add other fields as required
                ]);
            }

            return response()->json(['message' => 'Server inbounds processed successfully']);
        } catch (\Exception $e) {
            Log::error('Error processing server inbounds: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process server inbounds'], 500);
        }
    }

    // Add client to server inbound
    public function addClient(Request $request, $server_inbound_id)
    {
        try {
            // Call XUIService method to add client
            $response = $this->xuiService->addClient($server_inbound_id, $request->all());

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error adding client to server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add client to server inbound'], 500);
        }
    }

    // Delete client from server inbound
    public function deleteClient($server_inbound_id, $uuid)
    {
        try {
            // Call XUIService method to delete client
            $response = $this->xuiService->deleteClientFromInbound($server_inbound_id, $uuid);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error deleting client from server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete client from server inbound'], 500);
        }
    }

    // Edit client in server inbound
    public function editClient($server_inbound_id, $uuid, Request $request)
    {
        try {
            // Call XUIService method to edit client
            $response = $this->xuiService->editClientInInbound($server_inbound_id, $uuid, $request->all());

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error editing client in server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to edit client in server inbound'], 500);
        }
    }

    // Enable or disable client in server inbound
    public function toggleClientStatus($server_inbound_id, $uuid, $status)
    {
        try {
            // Call XUIService method to toggle client status
            $response = $this->xuiService->toggleClientStatusInInbound($server_inbound_id, $uuid, $status);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error toggling client status in server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to toggle client status in server inbound'], 500);
        }
    }

    // Fetch clients in server inbound
    public function fetchClients($server_inbound_id)
    {
        try {
            // Call XUIService method to fetch clients
            $response = $this->xuiService->fetchClientsInInbound($server_inbound_id);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error fetching clients in server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch clients in server inbound'], 500);
        }
    }

    // Implement other methods similarly based on your application's requirements and XUIService capabilities
}