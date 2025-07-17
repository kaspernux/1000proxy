<?php

namespace App\Http\Controllers;

use App\Models\ServerInbound;
use App\Models\ServerClient;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
            DB::beginTransaction();

            $xuiService = new XUIService($request->input('server_id'));
            $inboundResponse = $xuiService->addInbound($request);

            $data = [
                'server_id' => $request->input('server_id'),
                'remark' => $inboundResponse['inbound']['obj']['remark'],
                'listen' => $inboundResponse['inbound']['obj']['listen'],
                'port' => $inboundResponse['inbound']['obj']['port'],
                'protocol' => $inboundResponse['inbound']['obj']['protocol'],
                'settings' => $inboundResponse['inbound']['obj']['settings'],
                'streamSettings' => $inboundResponse['inbound']['obj']['streamSettings'],
                'sniffing' => $inboundResponse['inbound']['obj']['sniffing'],
                'enable' => $inboundResponse['inbound']['obj']['enable'],
                'expiry_time' => !empty($inboundResponse['inbound']['obj']['expiry_time']) ? date('Y-m-d H:i:s', $inboundResponse['inbound']['obj']['expiry_time']) : null,
            ];

            $serverInbound = ServerInbound::create($data);

            $defaultClient = $inboundResponse['client'];
            $clientData = [
                'server_inbound_id' => $serverInbound->id,
                'email' => $defaultClient['email'],
                'password' => $defaultClient['id'],
                'flow' => $defaultClient['flow'] ?? 'None',
                'limit_ip' => $defaultClient['limit_ip'],
                'totalGB' => $defaultClient['totalGB'],
                'expiry_time' => $defaultClient['expiry_time'] ? date('Y-m-d H:i:s', $defaultClient['expiry_time']) : null,
                'tg_id' => $defaultClient['tg_id'] ?? null,
                'subId' => $defaultClient['subId'],
                'enable' => $defaultClient['enable'],
                'reset' => $defaultClient['reset'] ?? null,
                'qr_code_sub' => $defaultClient['qr_code_sub'] ?? null,
                'qr_code_sub_json' => $defaultClient['qr_code_sub_json'] ?? null,
                'qr_code_client' => $defaultClient['qr_code_client'] ?? null,
            ];

            ServerClient::create($clientData);

            DB::commit();

            return response()->json($serverInbound, 201);
        } catch (\Exception $e) {
            DB::rollBack();

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

    public function addInbound(Request $request)
    {
        try {
            $response = $this->xuiService->addInboundAccount(
                $request->input('server_id'),
                $request->input('client_id'),
                $request->input('inbound_id'),
                $request->input('expiry_time'),
                $request->input('remark'),
                $request->input('volume'),
                $request->input('limit_ip', 1),
                $request->input('newarr', ''),
                $request->input('planId')
            );

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Error adding inbound: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function editInbound(Request $request)
    {
        try {
            $response = $this->xuiService->editInbound(
                $request->input('server_id'),
                $request->input('uniqid'),
                $request->input('uuid'),
                $request->input('protocol'),
                $request->input('netType', 'tcp'),
                $request->input('security', 'none'),
                $request->input('bypass', false)
            );

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Error editing inbound: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteInbound(Request $request)
    {
        try {
            $response = $this->xuiService->deleteInbound(
                $request->input('server_id'),
                $request->input('uuid'),
                $request->input('delete', 0)
            );

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Error deleting inbound: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
