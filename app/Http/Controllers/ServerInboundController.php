<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\ServerInbound;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServerInboundController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'up' => 'nullable|integer',
            'down' => 'nullable|integer',
            'total' => 'nullable|integer',
            'remark' => 'nullable|string',
            'enable' => 'required|boolean',
            'expiryTime' => 'nullable|integer',
            'clientStats' => 'nullable|json',
            'listen' => 'nullable|string',
            'port' => 'nullable|integer',
            'protocol' => 'nullable|string',
            'settings' => 'nullable|json',
            'streamSettings' => 'nullable|json',
            'tag' => 'nullable|string',
            'sniffing' => 'nullable|json',
        ]);

        DB::beginTransaction();

        try {
            // Assuming $request->server_id is used to find the Server model
            $server = Server::findOrFail($request->server_id);
            $xuiService = new XUIService($server);

            $xuiResponse = $xuiService->createInbound($request->all());

            if (isset($xuiResponse['error'])) {
                DB::rollBack();
                return response()->json($xuiResponse, 400);
            }

            $userId = $xuiResponse['userId'] ?? null;

            // Creating ServerInbound without server_id
            $inboundData = array_merge($request->all(), ['userId' => $userId]);
            $inbound = ServerInbound::create($inboundData);

            DB::commit();
            return response()->json($inbound, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getInbound(Request $request, $inboundId)
    {
        try {
            // Assuming $request->server_id is used to find the Server model
            $server = Server::findOrFail($request->server_id);
            $xuiService = new XUIService($server);

            $inbound = $xuiService->getInbound($inboundId);
            return response()->json($inbound);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $inboundId)
    {
        $request->validate([
            'up' => 'nullable|integer',
            'down' => 'nullable|integer',
            'total' => 'nullable|integer',
            'remark' => 'nullable|string',
            'enable' => 'nullable|boolean',
            'expiryTime' => 'nullable|integer',
            'clientStats' => 'nullable|json',
            'listen' => 'nullable|string',
            'port' => 'nullable|integer',
            'protocol' => 'nullable|string',
            'settings' => 'nullable|json',
            'streamSettings' => 'nullable|json',
            'tag' => 'nullable|string',
            'sniffing' => 'nullable|json',
        ]);

        DB::beginTransaction();

        try {
            // Assuming $request->server_id is used to find the Server model
            $server = Server::findOrFail($request->server_id);
            $xuiService = new XUIService($server);

            $xuiResponse = $xuiService->updateInbound($inboundId, $request->all());

            if (isset($xuiResponse['error'])) {
                DB::rollBack();
                return response()->json($xuiResponse, 400);
            }

            $inbound = ServerInbound::findOrFail($inboundId);
            $inbound->update($request->all());

            DB::commit();
            return response()->json($inbound, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete(Request $request, $inboundId)
    {
        DB::beginTransaction();

        try {
            // Assuming $request->server_id is used to find the Server model
            $server = Server::findOrFail($request->server_id);
            $xuiService = new XUIService($server);

            $xuiResponse = $xuiService->deleteInbound($inboundId);

            if (isset($xuiResponse['error'])) {
                DB::rollBack();
                return response()->json($xuiResponse, 400);
            }

            $inbound = ServerInbound::findOrFail($inboundId);
            $inbound->delete();

            DB::commit();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listInbounds(Request $request)
    {
        try {
            // Assuming $request->server_id is used to find the Server model
            $server = Server::findOrFail($request->server_id);
            $xuiService = new XUIService($server);

            $inbounds = $xuiService->listInbounds();
            return response()->json($inbounds);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteDepletedClients(Request $request, $inboundId = null)
    {
        try {
            // Assuming $request->server_id is used to find the Server model
            $server = Server::findOrFail($request->server_id);
            $xuiService = new XUIService($server);

            $response = $xuiService->deleteDepletedClients($inboundId);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
