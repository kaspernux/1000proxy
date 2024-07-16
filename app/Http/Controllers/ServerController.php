<?php

namespace App\Http\Controllers;

use App\Models\ServerInbound;
use App\Models\ServerClient;
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
            $settings = json_decode($inboundResponse['obj']['settings'], true);

            $data = [
                'server_id' => $request->server_id,
                'userId' => $request->userId,
                'up' => $inboundResponse['obj']['up'],
                'down' => $inboundResponse['obj']['down'],
                'total' => $inboundResponse['obj']['total'],
                'remark' => $inboundResponse['obj']['remark'],
                'enable' => $inboundResponse['obj']['enable'],
                'expiryTime' => $inboundResponse['obj']['expiryTime'] ? date('Y-m-d H:i:s', $inboundResponse['obj']['expiryTime']) : null,
                'clientStats' => $inboundResponse['obj']['clientStats'] ?? null,
                'listen' => $inboundResponse['obj']['listen'],
                'port' => $inboundResponse['obj']['port'],
                'protocol' => $inboundResponse['obj']['protocol'],
                'settings' => json_encode($settings),
                'streamSettings' => $inboundResponse['obj']['streamSettings'],
                'tag' => $inboundResponse['obj']['tag'],
                'sniffing' => $inboundResponse['obj']['sniffing'],
            ];

            $inbound = ServerInbound::create($data);

            // Store the default client associated with the new inbound
            $defaultClient = $settings['clients'][0];
            $defaultClientData = [
                'server_inbound_id' => $inbound->id,
                'email' => $defaultClient['email'],
                'password' => $defaultClient['id'], // Assuming the id is used as a password
                'flow' => $defaultClient['flow'] ?? 'None',
                'limitIp' => $defaultClient['limitIp'],
                'totalGB' => $defaultClient['totalGB'],
                'expiryTime' => $defaultClient['expiryTime'] ? date('Y-m-d H:i:s', $defaultClient['expiryTime']) : null,
                'tgId' => $defaultClient['tgId'] ?? null,
                'subId' => $defaultClient['subId'],
                'enable' => $defaultClient['enable'],
                'reset' => $defaultClient['reset'] ?? null,
                'qr_code_sub' => $defaultClient['qr_code_sub'] ?? null,
                'qr_code_sub_json' => $defaultClient['qr_code_sub_json'] ?? null,
                'qr_code_client' => $defaultClient['qr_code_client'] ?? null,
            ];

            ServerClient::create($defaultClientData);

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
