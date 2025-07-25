<?php

namespace App\Http\Controllers;

use App\Models\ServerInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServerInfoController extends Controller
{
    // Retrieve a server info
    public function show($id)
    {
        try {
            $info = ServerInfo::findOrFail($id);
            return response()->json($info);
        } catch (\Exception $e) {
            Log::error('Error fetching server info: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server info'], 500);
        }
    }

    // Update a server info
    public function update(Request $request, $id)
    {
        try {
            $info = ServerInfo::findOrFail($id);
            $info->update($request->all());
            return response()->json($info);
        } catch (\Exception $e) {
            Log::error('Error updating server info: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server info'], 500);
        }
    }

    // Delete a server info
    public function destroy($id)
    {
        try {
            $info = ServerInfo::findOrFail($id);
            $info->delete();
            return response()->json(['message' => 'Server info deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting server info: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete server info'], 500);
        }
    }

    // Add a new server info
    public function store(Request $request)
    {
        try {
            $info = ServerInfo::create($request->all());
            return response()->json($info, 201);
        } catch (\Exception $e) {
            Log::error('Error adding server info: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add server info'], 500);
        }
    }

    // List all server infos
    public function index()
    {
        try {
            $infos = ServerInfo::all();
            return response()->json($infos);
        } catch (\Exception $e) {
            Log::error('Error fetching server infos: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server infos'], 500);
        }
    }
}
