<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ServerInbound;
use App\Models\ServerPlan;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class ServerClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'server_clients';

    protected $fillable = [
        'server_inbound_id', 'email', 'password', 'flow', 'limitIp', 'totalGb', 'expiryTime', 'tgId', 'subId',
        'plan_id', 'enable', 'reset', 'qr_code_sub', 'qr_code_sub_json', 'qr_code_client', 'client_link', 'remote_sub_link',
        'remote_json_link', 'security', 'pbk', 'fp', 'sni', 'sid', 'spx', 'grpc_service_name', 'network_type',
        'tls_type', 'alpn', 'header_type', 'host', 'path', 'kcp_seed', 'kcp_type',
    ];

    protected $casts = [
        'totalGb' => 'integer',
        'expiryTime' => 'datetime',
        'enable' => 'boolean',
    ];

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class, 'plan_id');
    }

    public static function buildXuiClientLink(array $client, ServerInbound $inbound, $server): string
    {
        $protocol = strtolower($inbound->protocol);
        $uuid = $client['id'] ?? '';
        $remark = $client['email'] ?? 'Client';
        $ip = parse_url($server->panel_url, PHP_URL_HOST);
        $port = $inbound->port;

        $stream = is_array($inbound->streamSettings)
            ? $inbound->streamSettings
            : json_decode($inbound->streamSettings ?? '{}', true);

        $realityRoot = $stream['realitySettings'] ?? [];
        $reality = $realityRoot['settings'] ?? [];

        $params = [
            'type' => $stream['network'] ?? 'tcp',
            'security' => $stream['security'] ?? 'reality',
            'pbk' => $reality['publicKey'] ?? null,
            'fp' => $reality['fingerprint'] ?? null,
            'sni' => $realityRoot['serverNames'][0] ?? $realityRoot['serverName'] ?? $server->sni ?? null,
            'sid' => $realityRoot['shortIds'][0] ?? null,
            'spx' => $reality['spiderX'] ?? null,
            'alpn' => isset($realityRoot['alpn']) ? implode(',', (array) $realityRoot['alpn']) : null,
        ];

        $query = http_build_query(array_filter($params));

        switch ($protocol) {
            case 'vmess':
                $vmess = [
                    'v' => '2',
                    'ps' => $remark,
                    'add' => $ip,
                    'port' => (string) $port,
                    'id' => $uuid,
                    'aid' => '0',
                    'scy' => 'auto',
                    'net' => $params['type'],
                    'type' => $stream['httpSettings']['header']['type'] ?? 'none',
                    'host' => $stream['httpSettings']['host'] ?? null,
                    'path' => $stream['httpSettings']['path'] ?? '/',
                    'tls' => $params['security'],
                    'fp' => $params['fp'],
                    'alpn' => $params['alpn'],
                    'pbk' => $params['pbk'],
                    'sid' => $params['sid'],
                    'spiderX' => $params['spx'],
                    'serviceName' => $params['serviceName'] ?? null,
                    'grpcSecurity' => $params['grpcSecurity'] ?? null,
                    'kcpType' => $stream['kcpSettings']['type'] ?? null,
                    'kcpSeed' => $stream['kcpSettings']['seed'] ?? null,
                ];
                return 'vmess://' . base64_encode(json_encode($vmess, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            case 'trojan':
                return "trojan://{$uuid}@{$ip}:{$port}?{$query}#" . rawurlencode($remark);

            case 'vless':
            default:
                return "vless://{$uuid}@{$ip}:{$port}?{$query}#" . rawurlencode("Default Inbound-{$remark}");
        }
    }

    public static function fromRemoteClient(array $client, int $inboundId, ?string $clientLink = null): self
    {
        if (empty($client['subId']) || empty($client['id'])) {
            throw new \InvalidArgumentException('subId and id are required for QR generation.');
        }

        $inbound = ServerInbound::with('server')->findOrFail($inboundId);
        $server = $inbound->server;
        $subId = $client['subId'];

        $link = $clientLink ?: self::buildXuiClientLink($client, $inbound, $server);
        $subLink = $client['sub_link'] ?? "http://{$server->getPanelHost()}:{$server->getSubscriptionPort()}/sub_proxy/{$subId}";
        $jsonLink = $client['json_link'] ?? "http://{$server->getPanelHost()}:{$server->getSubscriptionPort()}/json_proxy/{$subId}";

        $qrDir = 'qr_codes';
        $qrClientPath = "{$qrDir}/client_{$subId}.png";
        $qrSubPath = "{$qrDir}/sub_{$subId}.png";
        $qrJsonPath = "{$qrDir}/json_{$subId}.png";

        QrCode::format('png')->size(400)->generate($link, storage_path("app/public/{$qrClientPath}"));
        QrCode::format('png')->size(400)->generate($subLink, storage_path("app/public/{$qrSubPath}"));
        QrCode::format('png')->size(400)->generate($jsonLink, storage_path("app/public/{$qrJsonPath}"));

        $query = parse_url($link, PHP_URL_QUERY);
        parse_str($query, $params);

        return self::updateOrCreate(
            ['subId' => $subId],
            [
                'server_inbound_id' => $inboundId,
                'email' => $client['email'] ?? null,
                'password' => $client['id'],
                'flow' => $params['flow'] ?? $client['flow'] ?? null,
                'limitIp' => $client['limitIp'] ?? 0,
                'totalGb' => isset($client['totalGB']) ? floor($client['totalGB'] / 1073741824) : 0,
                'expiryTime' => isset($client['expiryTime']) ? Carbon::createFromTimestampMs($client['expiryTime']) : null,
                'tgId' => $client['tgId'] ?? null,
                'enable' => $client['enable'] ?? true,
                'reset' => $client['reset'] ?? null,
                'qr_code_client' => $qrClientPath,
                'qr_code_sub' => $qrSubPath,
                'qr_code_sub_json' => $qrJsonPath,
                'client_link' => $link,
                'remote_sub_link' => $subLink,
                'remote_json_link' => $jsonLink,
                'security' => $params['security'] ?? null,
                'pbk' => $params['pbk'] ?? null,
                'fp' => $params['fp'] ?? null,
                'sni' => $params['sni'] ?? null,
                'sid' => $params['sid'] ?? null,
                'spx' => $params['spx'] ?? null,
                'alpn' => $params['alpn'] ?? null,
                'grpc_service_name' => $params['serviceName'] ?? null,
                'network_type' => $params['type'] ?? null,
                'tls_type' => $params['security'] ?? null,
                'host' => $params['host'] ?? null,
                'path' => $params['path'] ?? null,
                'header_type' => $params['headerType'] ?? null,
                'kcp_seed' => $params['kcpSeed'] ?? null,
                'kcp_type' => $params['kcpType'] ?? null,
            ]
        );
    }
}
