<?php

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
    public function show($id)
    {
        try {
            $client = $this->xuiService->getClientById($id);
            return response()->json($client);
        } catch (\Exception $e) {
            Log::error('Error fetching server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server client'], 500);
        }
    }

    // Update a server client
    public function update(Request $request, $id)
    {
        try {
            $client = $this->xuiService->updateClient($id, $request->all());
            return response()->json($client);
        } catch (\Exception $e) {
            Log::error('Error updating server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server client'], 500);
        }
    }

    // Delete a server client
    public function destroy($id)
    {
        try {
            $this->xuiService->deleteClient($id);
            return response()->json(['message' => 'Server client deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete server client'], 500);
        }
    }

    // Add a new server client
    public function store(Request $request)
    {
        try {
            $client = $this->xuiService->addClient($request->all());
            return response()->json($client, 201);
        } catch (\Exception $e) {
            Log::error('Error adding server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add server client'], 500);
        }
    }

    // List all server clients
    public function index()
    {
        try {
            $clients = $this->xuiService->getAllClients();
            return response()->json($clients);
        } catch (\Exception $e) {
            Log::error('Error fetching server clients: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server clients'], 500);
        }
    }
}