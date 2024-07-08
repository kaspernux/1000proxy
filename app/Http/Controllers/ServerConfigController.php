<?php

namespace App\Http\Controllers;

use App\Models\ServerConfig;
use App\Services\XUIService;
use Illuminate\Http\Request;

class ServerConfigController extends Controller
{
    // Retrieve a server config
    public function show(ServerConfig $serverConfig)
    {
        return response()->json($serverConfig);
    }

    // Update a server config
    public function update(Request $request, ServerConfig $serverConfig)
    {
        // Validate incoming request data here if needed

        // Update ServerConfig
        $serverConfig->update($request->all());

        // No need to update external system for ServerConfig based on user instructions

        return response()->json($serverConfig);
    }

    // Delete a server config
    public function destroy(ServerConfig $serverConfig)
    {
        // Delete ServerConfig
        $serverConfig->delete();

        return response()->json(['message' => 'Server config deleted successfully']);
    }

    // Add a new server config
    public function store(Request $request)
    {
        // Validate incoming request data here if needed

        // Create ServerConfig
        $serverConfig = ServerConfig::create($request->all());

        return response()->json($serverConfig, 201);
    }
}