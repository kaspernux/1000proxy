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

class ServerClient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'server_clients';

    protected $fillable = [
        'server_inbound_id',
        'email',
        'password',
        'flow',
        'limitIp',
        'totalGb',
        'expiryTime',
        'tgId',
        'subId',
        'plan_id',
        'enable',
        'reset',
        'qr_code_sub',
        'qr_code_sub_json',
        'qr_code_client',
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

    public static function fromRemoteClient(array $client, int $inboundId): self
{
    if (empty($client['subId'])) {
        throw new \InvalidArgumentException('subId is required to sync client.');
    }

    $inbound = ServerInbound::with('server')->findOrFail($inboundId);
    $server = $inbound->server;
    $subId = $client['subId'];
    $uuid = $client['id'] ?? null;
    $remark = rawurlencode($client['email'] ?? 'Client');
    $host = parse_url($server->panel_url, PHP_URL_HOST);
    $panelPort = parse_url($server->panel_url, PHP_URL_PORT) ?? 443;

    // Construct vless:// link
    $streamSettings = json_decode($inbound->streamSettings, true);
    $params = [
        'type' => $streamSettings['network'] ?? 'tcp',
        'security' => $server->security ?? 'tls',
    ];

    if (!empty($streamSettings['realitySettings'])) {
        $params += [
            'pbk' => $streamSettings['realitySettings']['publicKey'] ?? '',
            'fp' => $streamSettings['realitySettings']['fingerprint'] ?? '',
            'sni' => $server->sni ?? '',
            'sid' => $streamSettings['realitySettings']['shortId'] ?? '',
            'spx' => $streamSettings['realitySettings']['spiderX'] ?? '',
        ];
    }

    $query = http_build_query($params);
    $vlessLink = "vless://{$uuid}@{$host}:{$inbound->port}?{$query}#{$remark}";

    // Construct links
    $subscriptionLink = "http://{$host}:{$panelPort}/sub_proxy/{$subId}";
    $jsonLink = "http://{$host}:{$panelPort}/json_proxy/{$subId}";

    // Paths
    $qrSubPath = "qr_codes/sub_{$subId}.png";
    $qrJsonPath = "qr_codes/json_{$subId}.png";
    $qrClientPath = "qr_codes/client_{$subId}.png";

    // Only generate if file doesn't already exist
    if (!Storage::disk('public')->exists($qrSubPath)) {
        QrCode::format('png')->size(400)->generate($subscriptionLink, storage_path("app/public/{$qrSubPath}"));
    }

    if (!Storage::disk('public')->exists($qrJsonPath)) {
        QrCode::format('png')->size(400)->generate($jsonLink, storage_path("app/public/{$qrJsonPath}"));
    }

    if (!Storage::disk('public')->exists($qrClientPath)) {
        QrCode::format('png')->size(400)->generate($vlessLink, storage_path("app/public/{$qrClientPath}"));
    }

    return self::updateOrCreate(
        ['subId' => $subId],
        [
            'server_inbound_id' => $inboundId,
            'email' => $client['email'] ?? null,
            'password' => $uuid,
            'flow' => $client['flow'] ?? null,
            'limitIp' => $client['limitIp'] ?? 0,
            'totalGb' => isset($client['totalGB']) ? floor($client['totalGB'] / 1073741824) : 0,
            'expiryTime' => isset($client['expiryTime']) && $client['expiryTime'] > 0
                ? Carbon::createFromTimestampMs($client['expiryTime'])
                : null,
            'tgId' => $client['tgId'] ?? null,
            'enable' => $client['enable'] ?? true,
            'reset' => $client['reset'] ?? null,
            'qr_code_client' => $qrClientPath,
            'qr_code_sub' => $qrSubPath,
            'qr_code_sub_json' => $qrJsonPath,
        ]
    );
}

}
