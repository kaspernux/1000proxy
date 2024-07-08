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
            'server_id' => 'required|exists:servers,id',
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
            $server = Server::findOrFail($request->server_id);
            $xuiService = new XUIService($server);

            $xuiResponse = $xuiService->createInbound($request->all());

            if (isset($xuiResponse['error'])) {
                DB::rollBack();
                return response()->json($xuiResponse, 400);
            }

            $userId = $xuiResponse['userId']; // Assuming 'userId' is returned from the XUI server

            $inbound = ServerInbound::create(array_merge($request->all(), ['userId' => $userId]));

            DB::commit();
            return response()->json($inbound, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getInbound(Request $request, $inboundId)
    {
        $server = Server::findOrFail($request->server_id);
        $xuiService = new XUIService($server);

        $inbound = $xuiService->getInbound($inboundId);
        return response()->json($inbound);
    }

    public function update(Request $request, $inboundId)
    {
        $request->validate([
            'server_id' => 'required|exists:servers,id',
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
        $server = Server::findOrFail($request->server_id);
        $xuiService = new XUIService($server);

        $inbounds = $xuiService->listInbounds();
        return response()->json($inbounds);
    }

    public function deleteDepletedClients(Request $request, $inboundId = null)
    {
        $server = Server::findOrFail($request->server_id);
        $xuiService = new XUIService($server);

        $response = $xuiService->deleteDepletedClients($inboundId);
        return response()->json($response);
    }

}
