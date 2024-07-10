<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerConfig;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServerConfigController extends Controller
{
    protected $xuiService;

    public function __construct(XUIService $xuiService)
    {
        $this->xuiService = $xuiService;
    }

    // Retrieve a server configuration
    public function show(ServerConfig $serverConfig)
    {
        return response()->json($serverConfig);
    }

    // Update a server configuration
    public function update(Request $request, ServerConfig $serverConfig)
    {
        // Validate incoming request data here if needed

        // Update Server Configuration
        $serverConfig->update($request->all());

        return response()->json($serverConfig);
    }

    // Delete a server configuration
    public function destroy(ServerConfig $serverConfig)
    {
        // Delete Server Configuration
        $serverConfig->delete();

        return response()->json(['message' => 'Server configuration deleted successfully']);
    }

    // Add a new server configuration
    public function store(Request $request)
    {
        // Validate incoming request data here if needed

        // Create Server Configuration
        $serverConfig = ServerConfig::create($request->all());

        return response()->json($serverConfig, 201);
    }

    // List all server configurations
    public function index()
    {
        $serverConfigs = ServerConfig::all();
        return response()->json($serverConfigs);
    }

    // Example method to manage server configurations
    public function manageServerConfigs()
    {
        try {
            // Example: Fetch server configurations from remote XUI
            $remoteServerConfigs = $this->xuiService->fetchServerConfigs();

            // Example: Process server configurations
            foreach ($remoteServerConfigs as $remoteServerConfig) {
                // Process each remote server configuration as needed
                Log::info('Processing server configuration: ' . $remoteServerConfig['panel_url']);
                // Example: Add server configuration to local database if needed
                ServerConfig::create([
                    'panel_url' => $remoteServerConfig['panel_url'],
                    'server_id' => $remoteServerConfig['server_id'],
                    // Add other fields as required
                ]);
            }

            return response()->json(['message' => 'Server configurations processed successfully']);
        } catch (\Exception $e) {
            Log::error('Error processing server configurations: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process server configurations'], 500);
        }
    }

    // Fetch server configuration details
    public function fetchServerConfigDetails($server_id)
    {
        try {
            // Fetch server configuration details from XUI or local database
            $server = Server::findOrFail($server_id);
            $serverConfig = $server->serverConfig;

            return response()->json($serverConfig);
        } catch (\Exception $e) {
            Log::error('Error fetching server configuration details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server configuration details'], 500);
        }
    }

    // Update server configuration details
    public function updateServerConfigDetails(Request $request, $server_id)
    {
        try {
            // Validate incoming request data here if needed

            // Update Server Configuration Details
            $server = Server::findOrFail($server_id);
            $serverConfig = $server->serverConfig;
            $serverConfig->update($request->all());

            return response()->json($serverConfig);
        } catch (\Exception $e) {
            Log::error('Error updating server configuration details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server configuration details'], 500);
        }
    }

    // Implement other methods similarly based on your application's requirements and XUIService capabilities
}