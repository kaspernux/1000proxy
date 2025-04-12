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
            $server_id = $request->input('server_id');
            $serverInbound = ServerInbound::with('server')->findOrFail($request->input('server_inbound_id'));
            $server = $serverInbound->server;

            $xuiService = new XUIService($server_id);
            $clientResponse = $xuiService->addClientInbound($request->all());

            $uuid = $clientResponse['id'] ?? $request->input('password');
            $subId = $clientResponse['subId'] ?? \Illuminate\Support\Str::uuid()->toString();
            $remark = rawurlencode($clientResponse['email'] ?? 'Client');

            $host = parse_url($server->panel_url, PHP_URL_HOST);
            $port = $serverInbound->port;
            $panelPort = parse_url($server->panel_url, PHP_URL_PORT) ?? 443;

            // vless link
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

            $vlessLink = "vless://{$uuid}@{$host}:{$port}?" . http_build_query($params) . "#{$remark}";
            $subLink = "http://{$host}:{$panelPort}/sub_proxy/{$subId}";
            $jsonLink = "http://{$host}:{$panelPort}/json_proxy/{$subId}";

            // File paths
            $qrDir = "qr_codes/";
            $qrSubPath = "{$qrDir}sub_{$subId}.png";
            $qrJsonPath = "{$qrDir}json_{$subId}.png";
            $qrClientPath = "{$qrDir}client_{$subId}.png";

            // Generate QR code images
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(400)->generate($subLink, storage_path("app/public/{$qrSubPath}"));
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(400)->generate($jsonLink, storage_path("app/public/{$qrJsonPath}"));
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(400)->generate($vlessLink, storage_path("app/public/{$qrClientPath}"));

            // Save to DB
            $serverClient = ServerClient::create([
                'server_inbound_id' => $serverInbound->id,
                'email' => $clientResponse['email'],
                'password' => $uuid,
                'flow' => $clientResponse['flow'] ?? 'None',
                'limitIp' => $clientResponse['limitIp'] ?? 1,
                'totalGb' => isset($clientResponse['totalGB']) ? floor($clientResponse['totalGB'] / 1073741824) : 0,
                'expiryTime' => isset($clientResponse['expiryTime']) ? now()->createFromTimestampMs($clientResponse['expiryTime']) : null,
                'tgId' => $clientResponse['tgId'] ?? null,
                'subId' => $subId,
                'enable' => $clientResponse['enable'] ?? true,
                'reset' => $clientResponse['reset'] ?? null,
                'qr_code_sub' => $qrSubPath,
                'qr_code_sub_json' => $qrJsonPath,
                'qr_code_client' => $qrClientPath,
            ]);

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