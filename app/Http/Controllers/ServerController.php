<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServerController extends Controller
{
    // Retrieve all servers
    public function index()
    {
        try {
            return Server::all();
        } catch (\Exception $e) {
            Log::error('Error fetching servers: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch servers'], 500);
        }
    }

    // Retrieve a server
    public function show($id)
    {
        try {
            return Server::findOrFail($id);
        } catch (\Exception $e) {
            Log::error('Error fetching server: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server'], 500);
        }
    }

    // Create a new server
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $server = Server::create($data);

            // Log the successful creation
            Log::info('Server created locally', ['server_id' => $server->id]);

            return $server;
        } catch (\Exception $e) {
            Log::error('Error adding server: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add server'], 500);
        }
    }

    // Update a server
    public function update($id, Request $request)
    {
        try {
            $server = Server::findOrFail($id);
            $server->update($request->all());

            // Log the successful update
            Log::info('Server updated locally', ['server_id' => $server->id]);

            return $server;
        } catch (\Exception $e) {
            Log::error('Error updating server: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server'], 500);
        }
    }

    // Delete a server
    public function destroy($id)
    {
        try {
            $server = Server::findOrFail($id);
            $server->delete();

            // Log the successful deletion
            Log::info('Server deleted locally', ['server_id' => $server->id]);

            return response()->json(['message' => 'Server deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting server: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete server'], 500);
        }
    }
}
