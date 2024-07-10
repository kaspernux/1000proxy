<?php

namespace App\Http\Controllers;

use App\Models\ServerClient;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServerClientController extends Controller
{
    protected $xuiService;

    public function __construct(XUIService $xuiService)
    {
        $this->xuiService = $xuiService;
    }

    // Retrieve a server client
    public function show(ServerClient $serverClient)
    {
        return response()->json($serverClient);
    }

    // Update a server client
    public function update(Request $request, ServerClient $serverClient)
    {
        // Validate incoming request data here if needed

        // Update Server Client
        $serverClient->update($request->all());

        return response()->json($serverClient);
    }

    // Delete a server client
    public function destroy(ServerClient $serverClient)
    {
        // Delete Server Client
        $serverClient->delete();

        return response()->json(['message' => 'Server client deleted successfully']);
    }

    // Add a new server client
    public function store(Request $request)
    {
        // Validate incoming request data here if needed

        // Create Server Client
        $serverClient = ServerClient::create($request->all());

        return response()->json($serverClient, 201);
    }

    // List all server clients
    public function index()
    {
        $serverClients = ServerClient::all();
        return response()->json($serverClients);
    }

    // Example method to manage server clients
    public function manageServerClients()
    {
        try {
            // Example: Fetch server clients from remote XUI
            $remoteServerClients = $this->xuiService->fetchServerClients();

            // Example: Process server clients
            foreach ($remoteServerClients as $remoteServerClient) {
                // Process each remote server client as needed
                Log::info('Processing server client: ' . $remoteServerClient['uuid']);
                // Example: Add server client to local database if needed
                ServerClient::create([
                    'uuid' => $remoteServerClient['uuid'],
                    'server_id' => $remoteServerClient['server_id'],
                    // Add other fields as required
                ]);
            }

            return response()->json(['message' => 'Server clients processed successfully']);
        } catch (\Exception $e) {
            Log::error('Error processing server clients: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process server clients'], 500);
        }
    }

    // Enable or disable server client
    public function toggleServerClientStatus($uuid, $status)
    {
        try {
            // Call XUIService method to toggle server client status
            $response = $this->xuiService->toggleServerClientStatus($uuid, $status);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error toggling server client status: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to toggle server client status'], 500);
        }
    }

    // Fetch server client details
    public function fetchServerClientDetails($uuid)
    {
        try {
            // Call XUIService method to fetch server client details
            $response = $this->xuiService->fetchServerClientDetails($uuid);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error fetching server client details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server client details'], 500);
        }
    }

    // Implement other methods similarly based on your application's requirements and XUIService capabilities
}