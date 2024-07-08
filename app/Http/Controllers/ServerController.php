<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    // Retrieve a server
    public function show(Server $server)
    {
        return response()->json($server);
    }

    // Update a server
    public function update(Request $request, Server $server)
    {
        // Validate incoming request data here if needed

        // Update Server
        $server->update($request->all());

        return response()->json($server);
    }

    // Delete a server
    public function destroy(Server $server)
    {
        // Delete Server
        $server->delete();

        return response()->json(['message' => 'Server deleted successfully']);
    }

    // Add a new server
    public function store(Request $request)
    {
        // Validate incoming request data here if needed

        // Create Server
        $server = Server::create($request->all());

        return response()->json($server, 201);
    }

    // Batch create servers
    public function batchCreate(Request $request)
    {
        // Validate incoming request data here if needed
        $servers = collect($request->all())->map(function ($data) {
            return Server::create($data);
        });

        return response()->json($servers, 201);
    }

    public function exportDatabase(Request $request)
    {
        $server = Server::findOrFail($request->server_id);
        $xuiService = new XUIService($server);

        $response = $xuiService->exportDatabase();
        return response()->json($response);
    }
}
