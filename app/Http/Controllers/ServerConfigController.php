<?php

namespace App\Http\Controllers;

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
    public function show($id)
    {
        try {
            $config = ServerConfig::findOrFail($id);
            return response()->json($config);
        } catch (\Exception $e) {
            Log::error('Error fetching server configuration: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server configuration'], 500);
        }
    }

    // Update a server configuration
    public function update($id, Request $request)
    {
        try {
            $config = ServerConfig::findOrFail($id);
            $config->update($request->all());
            $this->xuiService->updateConfig($id, $request->all());
            return response()->json($config);
        } catch (\Exception $e) {
            Log::error('Error updating server configuration: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server configuration'], 500);
        }
    }

    // Delete a server configuration
    public function destroy($id)
    {
        try {
            $config = ServerConfig::findOrFail($id);
            $config->delete();
            $this->xuiService->deleteConfig($id);
            return response()->json(['message' => 'Server configuration deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting server configuration: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete server configuration'], 500);
        }
    }

    // Add a new server configuration
    public function store(Request $request)
    {
        try {
            $config = ServerConfig::create($request->all());
            return $config;
        } catch (\Exception $e) {
            Log::error('Error adding server configuration: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add server configuration'], 500);
        }
    }

    // List all server configurations
    public function index()
    {
        try {
            $configs = ServerConfig::all();
            return response()->json($configs);
        } catch (\Exception $e) {
            Log::error('Error fetching server configurations: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server configurations'], 500);
        }
    }
}