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
        'order_id', 'customer_id', 'status', 'provisioned_at', 'activated_at', 'suspended_at', 'terminated_at',
        'last_connection_at', 'traffic_limit_mb', 'traffic_used_mb', 'traffic_percentage_used', 'connection_stats',
        'performance_metrics', 'connection_count', 'last_traffic_sync_at', 'client_config', 'provisioning_log',
        'error_message', 'retry_count', 'auto_renew', 'next_billing_at', 'renewal_price',
        // 3X-UI API specific fields
        'tg_id',
        'sub_id',
        'limit_ip',
        'total_gb_bytes',
        'expiry_time',
        'reset_counter',
        'remote_client_id',
        'remote_inbound_id',
        'last_api_sync_at',
        'api_sync_log',
        'api_sync_status',
        'api_sync_error',
        'remote_up',
        'remote_down',
        'remote_total',
        'remote_client_config',
        'connection_ips',
        'last_ip_clear_at',
        'is_online',
        'last_online_check_at',
    ];

    protected $casts = [
        'totalGb' => 'integer',
        'total_gb_bytes' => 'integer',
        'expiryTime' => 'datetime',
        'expiry_time' => 'datetime',
        'enable' => 'boolean',
        'provisioned_at' => 'datetime',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'last_connection_at' => 'datetime',
        'last_traffic_sync_at' => 'datetime',
        'last_api_sync_at' => 'datetime',
        'last_ip_clear_at' => 'datetime',
        'last_online_check_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'auto_renew' => 'boolean',
        'is_online' => 'boolean',
        'remote_up' => 'integer',
        'remote_down' => 'integer',
        'remote_total' => 'integer',
        'connection_stats' => 'array',
        'performance_metrics' => 'array',
        'client_config' => 'array',
        'remote_client_config' => 'array',
        'provisioning_log' => 'array',
        'api_sync_log' => 'array',
        'connection_ips' => 'array',
        'renewal_price' => 'decimal:2',
        'traffic_percentage_used' => 'decimal:2',
    ];

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class, 'plan_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_server_clients')
                    ->withPivot(['provision_status', 'provision_error', 'provision_attempts'])
                    ->withTimestamps();
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

    // Enhanced lifecycle methods

    /**
     * Mark client as provisioned
     */
    public function markAsProvisioned(): void
    {
        $this->update([
            'status' => 'active',
            'provisioned_at' => now(),
            'activated_at' => now(),
        ]);
    }

    /**
     * Suspend the client
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'enable' => false,
            'error_message' => $reason,
        ]);

        // Update remote XUI if needed
        $this->syncStatusToRemote();
    }

    /**
     * Reactivate the client
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'suspended_at' => null,
            'enable' => true,
            'error_message' => null,
        ]);

        // Update remote XUI if needed
        $this->syncStatusToRemote();
    }

    /**
     * Terminate the client
     */
    public function terminate(string $reason = null): void
    {
        $this->update([
            'status' => 'terminated',
            'terminated_at' => now(),
            'enable' => false,
            'error_message' => $reason,
        ]);

        // Update remote XUI if needed
        $this->syncStatusToRemote();
    }

    /**
     * Check if client is expired
     */
    public function isExpired(): bool
    {
        return $this->expiryTime && $this->expiryTime->isPast();
    }

    /**
     * Check if client is near expiration
     */
    public function isNearExpiration(int $days = 7): bool
    {
        return $this->expiryTime && $this->expiryTime->isBefore(now()->addDays($days));
    }

    /**
     * Check if traffic limit is exceeded
     */
    public function isTrafficLimitExceeded(): bool
    {
        if ($this->traffic_limit_mb === null) {
            return false;
        }

        return $this->traffic_used_mb >= $this->traffic_limit_mb;
    }

    /**
     * Update traffic usage
     */
    public function updateTrafficUsage(int $usedMb): void
    {
        $percentage = $this->traffic_limit_mb > 0
            ? ($usedMb / $this->traffic_limit_mb) * 100
            : 0;

        $this->update([
            'traffic_used_mb' => $usedMb,
            'traffic_percentage_used' => min($percentage, 100),
            'last_traffic_sync_at' => now(),
        ]);

        // Check if limit exceeded and suspend if needed
        if ($this->isTrafficLimitExceeded() && $this->status === 'active') {
            $this->suspend('Traffic limit exceeded');
        }
    }

    /**
     * Record a connection
     */
    public function recordConnection(array $connectionData = []): void
    {
        $this->update([
            'last_connection_at' => now(),
            'connection_count' => $this->connection_count + 1,
        ]);

        // Update connection stats
        $stats = $this->connection_stats ?? [];
        $stats['total_connections'] = ($stats['total_connections'] ?? 0) + 1;
        $stats['last_connection'] = $connectionData;
        $stats['updated_at'] = now()->toISOString();

        $this->update(['connection_stats' => $stats]);
    }

    /**
     * Extend expiration date
     */
    public function extend(int $days): void
    {
        $newExpiry = $this->expiryTime
            ? $this->expiryTime->addDays($days)
            : now()->addDays($days);

        $this->update(['expiryTime' => $newExpiry]);
    }

    /**
     * Renew client subscription
     */
    public function renew(int $days = null): void
    {
        $plan = $this->plan;
        $renewalDays = $days ?? $plan->days;

        $this->extend($renewalDays);

        if ($this->status === 'expired') {
            $this->reactivate();
        }

        // Update next billing date
        $this->update([
            'next_billing_at' => $this->expiryTime->subDays(7), // Bill 7 days before expiry
        ]);
    }

    /**
     * Sync status to remote XUI panel
     */
    protected function syncStatusToRemote(): bool
    {
        try {
            $xuiService = new \App\Services\XUIService($this->inbound->server_id);

            // Update client enable/disable status
            $settings = [
                'enable' => $this->enable,
                'expiryTime' => $this->expiryTime ? $this->expiryTime->timestamp * 1000 : 0,
            ];

            $xuiService->updateClient($this->password, $settings);
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to sync client {$this->id} to remote: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get client configuration for download
     */
    public function getDownloadableConfig(): array
    {
        return [
            'client_link' => $this->client_link,
            'subscription_link' => $this->remote_sub_link,
            'json_link' => $this->remote_json_link,
            'qr_codes' => [
                'client' => $this->qr_code_client,
                'subscription' => $this->qr_code_sub,
                'json' => $this->qr_code_sub_json,
            ],
            'connection_details' => [
                'protocol' => $this->inbound->protocol,
                'server' => $this->inbound->server->ip,
                'port' => $this->inbound->port,
                'uuid' => $this->password,
                'security' => $this->security,
            ],
            'usage_info' => [
                'traffic_used_mb' => $this->traffic_used_mb,
                'traffic_limit_mb' => $this->traffic_limit_mb,
                'traffic_percentage' => $this->traffic_percentage_used,
                'expires_at' => $this->expiryTime?->toISOString(),
                'status' => $this->status,
            ],
        ];
    }

    // 3X-UI API Integration Methods

    /**
     * Create client data for 3X-UI API (add/update operations)
     */
    public function toXuiApiClientData(): array
    {
        return [
            'id' => $this->id, // UUID
            'flow' => $this->flow ?? '',
            'email' => $this->email,
            'limitIp' => $this->limit_ip ?? 0,
            'totalGB' => $this->total_gb_bytes ?? 0,
            'expiryTime' => $this->expiry_time ? $this->expiry_time->timestamp * 1000 : 0, // 3X-UI uses milliseconds
            'enable' => $this->enable,
            'tgId' => $this->tg_id ?? '',
            'subId' => $this->sub_id ?? '',
            'reset' => $this->reset_counter ?? 0,
        ];
    }

    /**
     * Create 3X-UI API client settings JSON for inbound operations
     */
    public function toXuiApiClientSettingsString(): string
    {
        $clientData = $this->toXuiApiClientData();

        return json_encode([
            'clients' => [$clientData]
        ]);
    }

    /**
     * Update client from 3X-UI API response data
     */
    public function updateFromXuiApiResponse(array $data): void
    {
        $this->fill([
            'id' => $data['id'] ?? $this->id,
            'flow' => $data['flow'] ?? $this->flow,
            'email' => $data['email'] ?? $this->email,
            'limit_ip' => $data['limitIp'] ?? $this->limit_ip,
            'total_gb_bytes' => $data['totalGB'] ?? $this->total_gb_bytes,
            'expiry_time' => isset($data['expiryTime']) && $data['expiryTime'] > 0
                ? Carbon::createFromTimestamp($data['expiryTime'] / 1000)
                : null,
            'enable' => $data['enable'] ?? $this->enable,
            'tg_id' => $data['tgId'] ?? $this->tg_id,
            'sub_id' => $data['subId'] ?? $this->sub_id,
            'reset_counter' => $data['reset'] ?? $this->reset_counter,
            'last_api_sync_at' => now(),
            'api_sync_status' => 'success',
            'api_sync_error' => null,
        ]);

        // Store the complete client config for future reference
        $this->remote_client_config = $data;
    }

    /**
     * Update client from 3X-UI API client statistics
     */
    public function updateFromXuiApiClientStats(array $stats): void
    {
        $this->fill([
            'remote_client_id' => $stats['id'] ?? null,
            'remote_inbound_id' => $stats['inboundId'] ?? null,
            'enable' => $stats['enable'] ?? $this->enable,
            'email' => $stats['email'] ?? $this->email,
            'remote_up' => $stats['up'] ?? 0,
            'remote_down' => $stats['down'] ?? 0,
            'remote_total' => $stats['total'] ?? 0,
            'expiry_time' => isset($stats['expiryTime']) && $stats['expiryTime'] > 0
                ? Carbon::createFromTimestamp($stats['expiryTime'] / 1000)
                : null,
            'reset_counter' => $stats['reset'] ?? $this->reset_counter,
            'last_traffic_sync_at' => now(),
        ]);

        // Update traffic usage percentage
        $this->updateTrafficUsagePercentage();
    }

    /**
     * Update traffic usage percentage based on remote traffic data
     */
    public function updateTrafficUsagePercentage(): void
    {
        if ($this->total_gb_bytes > 0) {
            $this->traffic_percentage_used = min(100.0, ($this->remote_total / $this->total_gb_bytes) * 100);
        } else {
            $this->traffic_percentage_used = 0.0;
        }
    }

    /**
     * Check if this client is synchronized with 3X-UI
     */
    public function isSyncedWithXui(): bool
    {
        return !empty($this->remote_client_id) &&
               $this->api_sync_status === 'success' &&
               $this->last_api_sync_at &&
               $this->last_api_sync_at->diffInMinutes(now()) < 60; // Synced within last hour
    }

    /**
     * Mark API sync as failed
     */
    public function markApiSyncFailed(string $error): void
    {
        $this->update([
            'api_sync_status' => 'error',
            'api_sync_error' => $error,
            'last_api_sync_at' => now(),
        ]);
    }

    /**
     * Check if client is over traffic limit
     */
    public function isOverTrafficLimit(): bool
    {
        return $this->total_gb_bytes > 0 && $this->remote_total >= $this->total_gb_bytes;
    }

    /**
     * Check if client is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_time && $this->expiry_time->isPast();
    }

    /**
     * Check if client is depleted (over limit or expired)
     */
    public function isDepleted(): bool
    {
        return $this->isOverTrafficLimit() || $this->isExpired();
    }

    /**
     * Get remaining traffic in bytes
     */
    public function getRemainingTrafficBytes(): int
    {
        if ($this->total_gb_bytes <= 0) {
            return 0; // Unlimited
        }

        return max(0, $this->total_gb_bytes - $this->remote_total);
    }

    /**
     * Get remaining days until expiry
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->expiry_time) {
            return null; // No expiry
        }

        $days = $this->expiry_time->diffInDays(now(), false);
        return max(0, $days);
    }

    /**
     * Update connection IPs from 3X-UI API
     */
    public function updateConnectionIpsFromXuiApi(array $ips): void
    {
        $this->update([
            'connection_ips' => $ips,
            'last_online_check_at' => now(),
        ]);
    }

    /**
     * Clear connection IPs (equivalent to 3X-UI clearClientIps)
     */
    public function clearConnectionIps(): void
    {
        $this->update([
            'connection_ips' => [],
            'last_ip_clear_at' => now(),
        ]);
    }

    /**
     * Reset traffic (equivalent to 3X-UI reset traffic)
     */
    public function resetTraffic(): void
    {
        $this->update([
            'remote_up' => 0,
            'remote_down' => 0,
            'remote_total' => 0,
            'reset_counter' => $this->reset_counter + 1,
            'last_traffic_sync_at' => now(),
        ]);

        $this->updateTrafficUsagePercentage();
    }

    /**
     * Get client status for monitoring
     */
    public function getMonitoringStatus(): array
    {
        return [
            'is_online' => $this->is_online,
            'is_enabled' => $this->enable,
            'is_expired' => $this->isExpired(),
            'is_over_limit' => $this->isOverTrafficLimit(),
            'is_depleted' => $this->isDepleted(),
            'traffic_percentage' => $this->traffic_percentage_used,
            'remaining_days' => $this->getRemainingDays(),
            'remaining_traffic_mb' => $this->getRemainingTrafficBytes() / 1024 / 1024,
            'last_sync' => $this->last_api_sync_at?->toISOString(),
        ];
    }

    /**
     * Generate unique email for 3X-UI if not set
     */
    public function generateEmailIfMissing(): void
    {
        if (empty($this->email)) {
            $this->email = 'client_' . $this->id . '_' . Str::random(8);
        }
    }

    /**
     * Generate unique UUID if not set
     */
    public function generateUuidIfMissing(): void
    {
        if (empty($this->id) || !Str::isUuid($this->id)) {
            $this->id = Str::uuid();
        }
    }

    /**
     * Generate unique subscription ID if not set
     */
    public function generateSubIdIfMissing(): void
    {
        if (empty($this->sub_id)) {
            $this->sub_id = Str::lower(Str::random(16));
        }
    }

    /**
     * Ensure all required fields are set for 3X-UI API operations
     */
    public function ensureApiReadiness(): void
    {
        $this->generateUuidIfMissing();
        $this->generateEmailIfMissing();
        $this->generateSubIdIfMissing();

        // Set default values if missing
        $this->flow = $this->flow ?? '';
        $this->limit_ip = $this->limit_ip ?? 0;
        $this->total_gb_bytes = $this->total_gb_bytes ?? 0;
        $this->enable = $this->enable ?? true;
        $this->tg_id = $this->tg_id ?? '';
        $this->reset_counter = $this->reset_counter ?? 0;
    }
}
