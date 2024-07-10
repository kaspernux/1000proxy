<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerInfo;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServerInfoController extends Controller
{
    protected $xuiService;

    public function __construct(XUIService $xuiService)
    {
        $this->xuiService = $xuiService;
    }

    // Retrieve a server info
    public function show(ServerInfo $serverInfo)
    {
        return response()->json($serverInfo);
    }

    // Update a server info
    public function update(Request $request, ServerInfo $serverInfo)
    {
        // Validate incoming request data here if needed

        // Update Server Info
        $serverInfo->update($request->all());

        return response()->json($serverInfo);
    }

    // Delete a server info
    public function destroy(ServerInfo $serverInfo)
    {
        // Delete Server Info
        $serverInfo->delete();

        return response()->json(['message' => 'Server info deleted successfully']);
    }

    // Add a new server info
    public function store(Request $request)
    {
        // Validate incoming request data here if needed

        // Create Server Info
        $serverInfo = ServerInfo::create($request->all());

        return response()->json($serverInfo, 201);
    }

    // List all server infos
    public function index()
    {
        $serverInfos = ServerInfo::all();
        return response()->json($serverInfos);
    }

    // Example method to manage server infos
    public function manageServerInfos()
    {
        try {
            // Example: Fetch server infos from remote XUI
            $remoteServerInfos = $this->xuiService->fetchServerInfos();

            // Example: Process server infos
            foreach ($remoteServerInfos as $remoteServerInfo) {
                // Process each remote server info as needed
                Log::info('Processing server info: ' . $remoteServerInfo['title']);
                // Example: Add server info to local database if needed
                ServerInfo::create([
                    'server_id' => $remoteServerInfo['server_id'],
                    'title' => $remoteServerInfo['title'],
                    // Add other fields as required
                ]);
            }

            return response()->json(['message' => 'Server infos processed successfully']);
        } catch (\Exception $e) {
            Log::error('Error processing server infos: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process server infos'], 500);
        }
    }

    // Fetch server info details
    public function fetchServerInfoDetails($server_id)
    {
        try {
            // Fetch server info details from XUI or local database
            $server = Server::findOrFail($server_id);
            $serverInfo = $server->serverInfo;

            return response()->json($serverInfo);
        } catch (\Exception $e) {
            Log::error('Error fetching server info details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server info details'], 500);
        }
    }

    // Update server info details
    public function updateServerInfoDetails(Request $request, $server_id)
    {
        try {
            // Validate incoming request data here if needed

            // Update Server Info Details
            $server = Server::findOrFail($server_id);
            $serverInfo = $server->serverInfo;
            $serverInfo->update($request->all());

            return response()->json($serverInfo);
        } catch (\Exception $e) {
            Log::error('Error updating server info details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server info details'], 500);
        }
    }

    // Implement other methods similarly based on your application's requirements and XUIService capabilities
}