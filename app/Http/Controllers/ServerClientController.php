<?php

namespace App\Http\Controllers;

use App\Models\ServerClient;
use App\Models\ServerInbound;
use App\Services\XUIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class ServerClientController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $clients = ServerClient::all();
            return response()->json($clients);
        } catch (\Exception $e) {
            Log::error('Error fetching server clients: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server clients'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $client = ServerClient::findOrFail($id);
            return response()->json($client);
        } catch (\Exception $e) {
            Log::error('Error fetching server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch server client'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Extract server_id from request
            $server_id = $request->input('server_id');

            // Get server inbound
            $serverInbound = ServerInbound::findOrFail($request->input('server_inbound_id'));

            // Create client on remote server using XUIService
            $xuiService = new XUIService($server_id);
            $clientResponse = $xuiService->addClientInbound($request->all());

            // Generate QR codes if links are returned
            $uuid = $clientResponse['id'] ?? $request->input('password');
            $subId = $clientResponse['subId'] ?? Str::uuid();
            $remark = rawurlencode($clientResponse['email'] ?? 'Client');
            $server = $serverInbound->server;
            $host = parse_url($server->panel_url, PHP_URL_HOST);
            $port = $serverInbound->port;
            $panelPort = parse_url($server->panel_url, PHP_URL_PORT) ?? 443;

            // Build vless link
            $stream = json_decode($serverInbound->streamSettings, true);
            $params = [
                'type' => $stream['network'] ?? 'tcp',
                'security' => $server->security ?? 'tls',
            ];
            if (!empty($stream['realitySettings'])) {
                $params += [
                    'pbk' => $stream['realitySettings']['publicKey'] ?? '',
                    'fp' => $stream['realitySettings']['fingerprint'] ?? '',
                    'sni' => $server->sni ?? '',
                    'sid' => $stream['realitySettings']['shortId'] ?? '',
                    'spx' => $stream['realitySettings']['spiderX'] ?? '',
                ];
            }
            $query = http_build_query($params);
            $vless = "vless://{$uuid}@{$host}:{$port}?{$query}#{$remark}";

            // Build sub links
            $subLink = "http://{$host}:{$panelPort}/sub_proxy/{$subId}";
            $jsonLink = "http://{$host}:{$panelPort}/json_proxy/{$subId}";

            // QR paths
            $qrDir = "qr_codes/";
            $qrSubPath = "{$qrDir}sub_{$subId}.png";
            $qrJsonPath = "{$qrDir}json_{$subId}.png";
            $qrClientPath = "{$qrDir}client_{$subId}.png";

            // Generate and store QR codes
            QrCode::format('png')->size(400)->generate($subLink, storage_path("app/public/{$qrSubPath}"));
            QrCode::format('png')->size(400)->generate($jsonLink, storage_path("app/public/{$qrJsonPath}"));
            QrCode::format('png')->size(400)->generate($vless, storage_path("app/public/{$qrClientPath}"));

            // Save client data to the local database
            $clientData = [
                'server_inbound_id' => $serverInbound->id,
                'email' => $clientResponse['email'],
                'password' => $request->input('password'),
                'flow' => $clientResponse['flow'] ?? 'None',
                'limitIp' => $clientResponse['limitIp'],
                'totalGB' => $clientResponse['totalGB'],
                'expiryTime' => $clientResponse['expiryTime'],
                'tgId' => $clientResponse['tgId'] ?? null,
                'subId' => $clientResponse['subId'],
                'enable' => $clientResponse['enable'],
                'reset' => $clientResponse['reset'] ?? null,
                'qr_code_sub' => $qrSubPath,
                'qr_code_sub_json' => $qrJsonPath,
                'qr_code_client' => $qrClientPath,
            ];

            $serverClient = ServerClient::create($clientData);

            return response()->json($serverClient, 201);
        } catch (\Exception $e) {
            Log::error('Error adding server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to add server client'], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $serverClient = ServerClient::findOrFail($id);
            $server_id = $serverClient->inbound->server_id;

            // Update client on remote server using XUIService
            $xuiService = new XUIService($server_id);
            $clientResponse = $xuiService->updateClient($request->all());

            // Update client data in the local database
            $serverClient->update($clientResponse['client']);

            return response()->json($serverClient);
        } catch (\Exception $e) {
            Log::error('Error updating server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update server client'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $serverClient = ServerClient::findOrFail($id);
            $server_id = $serverClient->inbound->server_id;

            // Delete client on remote server using XUIService
            $xuiService = new XUIService($server_id);
            $xuiService->deleteClient($serverClient->id);

            // Delete client data from the local database
            $serverClient->delete();

            return response()->json(['message' => 'Server client deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting server client: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete server client'], 500);
        }
    }
}