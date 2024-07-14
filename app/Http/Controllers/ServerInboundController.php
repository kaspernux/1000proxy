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

    public function show($id)
    {
        try {
            $serverInbound = ServerInbound::findOrFail($id);
            $inbound = $this->xuiService->getInboundById($serverInbound->server_id, $id);
            return response()->json($inbound);
        } catch (\Exception $e) {
            Log::error('Error fetching server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server inbound'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $serverInbound = ServerInbound::findOrFail($id);
            $inbound = $this->xuiService->updateInbound($serverInbound->server_id, $id, $request->all());
            return response()->json($inbound);
        } catch (\Exception $e) {
            Log::error('Error updating server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server inbound'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $serverInbound = ServerInbound::findOrFail($id);
            $this->xuiService->deleteInbound($serverInbound->server_id, $id);
            $serverInbound->delete();
            return response()->json(['message' => 'Server inbound deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete server inbound'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $xuiService = new XUIService($request->server_id);
            $inboundResponse = $xuiService->addInbound($request);

            // Extract relevant data from the response to store in the local database
            $data = [
                'server_id' => $request->server_id,
                'remark' => $inboundResponse['obj']['remark'],
                'listen' => $inboundResponse['obj']['listen'],
                'port' => $inboundResponse['obj']['port'],
                'protocol' => $inboundResponse['obj']['protocol'],
                'expiryTime' => $inboundResponse['obj']['expiryTime'],
                'settings' => $inboundResponse['obj']['settings'],
                'streamSettings' => $inboundResponse['obj']['streamSettings'],
                'sniffing' => $inboundResponse['obj']['sniffing'],
            ];

            $inbound = ServerInbound::create($data);

            return response()->json($inbound, 201);
        } catch (\Exception $e) {
            Log::error('Error adding server inbound: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add server inbound'], 500);
        }
    }

    public function index()
    {
        try {
            $inbounds = ServerInbound::all();
            return response()->json($inbounds);
        } catch (\Exception $e) {
            Log::error('Error fetching server inbounds: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server inbounds'], 500);
        }
    }
}
