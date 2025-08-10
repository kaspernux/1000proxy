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
use App\Services\QrCodeService;
use Illuminate\Support\Str;

class ServerClient extends Model
{
    use \App\Traits\LogsActivity;
    use HasFactory, SoftDeletes;

    protected $table = 'server_clients';

    // Primary key is a UUID string (no auto-increment)
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        // Core identification fields
        'id',                       // UUID for 3X-UI compatibility
        'server_inbound_id',
        'email',                    // 3X-UI client email identifier
        'password',                 // Legacy field

        // 3X-UI API specific fields (exact match with API)
        'flow',                     // 3X-UI flow control
        'limit_ip',                 // 3X-UI IP connection limit
        'total_gb_bytes',           // 3X-UI traffic limit (totalGB in bytes)
        'expiry_time',              // 3X-UI expiry timestamp (milliseconds)
        'enable',                   // 3X-UI client enabled status
        'tg_id',                    // 3X-UI Telegram ID
        'sub_id',                   // 3X-UI subscription ID (subId)
        'reset',                    // 3X-UI reset counter/timestamp

        // 3X-UI remote sync fields
        'remote_client_id',         // 3X-UI client stats ID
        'remote_inbound_id',        // 3X-UI inbound ID
        'remote_up',                // 3X-UI upload bytes
        'remote_down',              // 3X-UI download bytes
        'remote_total',             // 3X-UI total bytes
        'remote_client_config',     // Full 3X-UI client configuration

        // Connection and IP management (3X-UI specific)
        'connection_ips',           // Client IP addresses
        'last_ip_clear_at',         // Last IP clear timestamp
        'is_online',                // Current online status
        'last_online_check_at',     // Last online check timestamp

        // API sync tracking
        'last_api_sync_at',
        'api_sync_log',
        'api_sync_status',
        'api_sync_error',
        'last_traffic_sync_at',

        // Local management fields
        'plan_id',
        'order_id',
        'customer_id',
        'status',
        'provisioned_at',
        'activated_at',
        'suspended_at',
        'terminated_at',
        'last_connection_at',
        'traffic_limit_mb',
        'traffic_used_mb',
        'traffic_percentage_used',
        'connection_stats',
        'performance_metrics',
        'connection_count',
        'client_config',
        'provisioning_log',
        'error_message',
        'retry_count',
        'auto_renew',
        'next_billing_at',
        'renewal_price',

        // Legacy QR and link fields
        'qr_code_sub',
        'qr_code_sub_json',
        'qr_code_client',
        'client_link',
        'remote_sub_link',
        'remote_json_link',

        // Legacy connection fields
        'security',
        'pbk',
        'fp',
        'sni',
        'sid',
        'spx',
        'grpc_service_name',
        'network_type',
        'tls_type',
        'alpn',
        'header_type',
        'host',
        'path',
        'kcp_seed',
        'kcp_type',
    ];

    protected $casts = [
        // 3X-UI specific fields (matching API format)
        'id' => 'string',               // UUID string
        'total_gb_bytes' => 'integer',  // 3X-UI totalGB in bytes
        'expiry_time' => 'integer',     // 3X-UI expiry timestamp (milliseconds)
        'enable' => 'boolean',          // 3X-UI enabled status
        'limit_ip' => 'integer',        // 3X-UI IP connection limit
        'reset' => 'integer',           // 3X-UI reset counter/timestamp
        'remote_client_id' => 'integer', // 3X-UI client stats ID
        'remote_inbound_id' => 'integer', // 3X-UI inbound ID
        'remote_up' => 'integer',       // 3X-UI upload bytes
        'remote_down' => 'integer',     // 3X-UI download bytes
        'remote_total' => 'integer',    // 3X-UI total bytes
        'is_online' => 'boolean',       // 3X-UI online status

        // Local management fields
        'total_gb' => 'integer',        // Local traffic limit (GB)
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

        // JSON fields
        'connection_stats' => 'array',
        'performance_metrics' => 'array',
        'client_config' => 'array',
        'remote_client_config' => 'array',    // Full 3X-UI client config
        'provisioning_log' => 'array',
        'api_sync_log' => 'array',
        'connection_ips' => 'array',          // 3X-UI client IP addresses

        // Decimal fields
        'renewal_price' => 'decimal:2',
        'traffic_percentage_used' => 'decimal:2',
    ];

    protected $appends = [
        'bandwidth_used_mb'
    ];

    /**
     * Computed bandwidth used in MB (prefers traffic_used_mb, fallback to remote_up+remote_down).
     */
    public function getBandwidthUsedMbAttribute(): float
    {
        $direct = $this->traffic_used_mb;
        if (is_numeric($direct) && $direct >= 0) {
            return (float) $direct;
        }
        $sumBytes = (int) ($this->remote_up ?? 0) + (int) ($this->remote_down ?? 0);
        return round($sumBytes / 1048576, 2); // bytes -> MB
    }



    public function inbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }

    /**
     * Alias relationship for compatibility where 'serverInbound' is referenced instead of 'inbound'.
     */
    public function serverInbound(): BelongsTo
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

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }
    
    public function orders(): BelongsToMany
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
        // Normalize subscription ID key from remote payload
        $subIdKey = $client['subId'] ?? $client['sub_id'] ?? null;
        if (empty($subIdKey) || empty($client['id'])) {
            throw new \InvalidArgumentException('subId/sub_id and id are required for client creation.');
        }

        $inbound = ServerInbound::with('server')->findOrFail($inboundId);
        $server = $inbound->server;
    $subId = $subIdKey;

        $link = $clientLink ?: self::buildXuiClientLink($client, $inbound, $server);
        $subLink = $client['sub_link'] ?? "http://{$server->getPanelHost()}:{$server->getSubscriptionPort()}/sub_proxy/{$subId}";
        $jsonLink = $client['json_link'] ?? "http://{$server->getPanelHost()}:{$server->getSubscriptionPort()}/json_proxy/{$subId}";

        $qrDir = 'qr_codes';
        $qrClientPath = "{$qrDir}/client_{$subId}.png";
        $qrSubPath = "{$qrDir}/sub_{$subId}.png";
        $qrJsonPath = "{$qrDir}/json_{$subId}.png";

        // Use the new QrCodeService for branded QR codes
        try {
            $qrCodeService = app(QrCodeService::class);

            // Generate branded QR codes with 1000 Proxies styling
            file_put_contents(
                storage_path("app/public/{$qrClientPath}"),
                base64_decode(str_replace('data:image/png;base64,', '', $qrCodeService->generateClientQrCode($link)))
            );

            file_put_contents(
                storage_path("app/public/{$qrSubPath}"),
                base64_decode(str_replace('data:image/png;base64,', '', $qrCodeService->generateSubscriptionQrCode($subLink)))
            );

            file_put_contents(
                storage_path("app/public/{$qrJsonPath}"),
                base64_decode(str_replace('data:image/png;base64,', '', $qrCodeService->generateSubscriptionQrCode($jsonLink)))
            );
        } catch (\Exception $e) {
            // Fallback to simple QR code generation using our service
            try {
                $qrCodeService = app(\App\Services\QrCodeService::class);
                file_put_contents(
                    storage_path("app/public/{$qrClientPath}"),
                    $qrCodeService->generateBrandedQrCode($link, 400, 'png', ['style' => 'square'])
                );
                file_put_contents(
                    storage_path("app/public/{$qrSubPath}"),
                    $qrCodeService->generateBrandedQrCode($subLink, 400, 'png', ['style' => 'square'])
                );
                file_put_contents(
                    storage_path("app/public/{$qrJsonPath}"),
                    $qrCodeService->generateBrandedQrCode($jsonLink, 400, 'png', ['style' => 'square'])
                );
            } catch (\Exception $fallbackException) {
                \Log::warning("QR generation completely failed for client {$subId}: " . $fallbackException->getMessage());
            }
        }

        $query = parse_url($link, PHP_URL_QUERY);
        parse_str($query, $params);

        // We key lookup by sub_id for idempotency, but ensure primary UUID id is set on creation.
        $attributes = ['sub_id' => $subId];
        $values = [
            'id' => $client['id'], // primary key; required because table has no default
            'server_inbound_id' => $inboundId,
            'email' => $client['email'] ?? null,
            'password' => $client['id'],
            'flow' => $params['flow'] ?? $client['flow'] ?? null,
            'limit_ip' => $client['limit_ip'] ?? 0,
            'total_gb_bytes' => $client['totalGB'] ?? 0,  // Store 3X-UI totalGB bytes directly
            'expiry_time' => $client['expiry_time'] ?? 0,  // Store 3X-UI expiry_time milliseconds directly
            'tg_id' => $client['tg_id'] ?? null,
            'enable' => $client['enable'] ?? true,
            // 'reset' must be non-null due to DB constraint; default to 0
            'reset' => $client['reset'] ?? 0,
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
        ];

        // If record exists (same sub_id), do NOT overwrite primary key id.
        $existing = self::where($attributes)->first();
        if ($existing) {
            unset($values['id']);
            $existing->update($values);
            return $existing->refresh();
        }
        return self::create(array_merge($attributes, $values));
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
     * Check if client is expired (3X-UI uses milliseconds timestamp)
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_time || $this->expiry_time == 0) {
            return false; // 0 means never expires in 3X-UI
        }

        // 3X-UI uses milliseconds, convert to seconds for comparison
        $expiryTimestamp = $this->expiry_time / 1000;
        return $expiryTimestamp < time();
    }

    /**
     * Check if client is near expiration
     */
    public function isNearExpiration(int $days = 7): bool
    {
        if (!$this->expiry_time || $this->expiry_time == 0) {
            return false; // 0 means never expires in 3X-UI
        }

        // 3X-UI uses milliseconds, convert to seconds for comparison
        $expiryTimestamp = $this->expiry_time / 1000;
        $daysLaterTimestamp = now()->addDays($days)->timestamp;

        return $expiryTimestamp < $daysLaterTimestamp;
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
        if ($this->expiry_time && $this->expiry_time > 0) {
            // Convert current expiry from milliseconds to timestamp, add days, convert back to milliseconds
            $currentExpirySeconds = $this->expiry_time / 1000;
            $newExpirySeconds = $currentExpirySeconds + ($days * 24 * 60 * 60);
            $newExpiryMs = $newExpirySeconds * 1000;
        } else {
            // If no expiry set, set to days from now in milliseconds
            $newExpiryMs = now()->addDays($days)->timestamp * 1000;
        }

        $this->update(['expiry_time' => $newExpiryMs]);
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

        // Update next billing date (7 days before expiry, converted from milliseconds)
        $expirySeconds = $this->expiry_time / 1000;
        $billingTimestamp = $expirySeconds - (7 * 24 * 60 * 60); // 7 days before expiry
        $this->update([
            'next_billing_at' => \Carbon\Carbon::createFromTimestamp($billingTimestamp),
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
                'expiry_time' => $this->expiry_time ?? 0,  // Already in milliseconds, use directly
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
                'expires_at' => $this->expiry_time?->toISOString(),
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
            'limit_ip' => $this->limit_ip ?? 0,
            'totalGB' => $this->total_gb_bytes ?? 0, // 3X-UI uses totalGB for bytes
            'expiry_time' => $this->expiry_time ?? 0, // 3X-UI uses milliseconds
            'enable' => $this->enable,
            'tg_id' => $this->tg_id ?? '',
                'subId' => $this->sub_id ?? '',
            'reset' => $this->reset ?? 0,
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
            'limit_ip' => $data['limit_ip'] ?? $this->limit_ip,
            'total_gb_bytes' => $data['totalGB'] ?? $this->total_gb_bytes,
            'expiry_time' => $data['expiry_time'] ?? $this->expiry_time,
            'enable' => $data['enable'] ?? $this->enable,
            'tg_id' => $data['tg_id'] ?? $this->tg_id,
            'sub_id' => $data['subId'] ?? $this->sub_id,
            'reset' => $data['reset'] ?? $this->reset,
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
            'expiry_time' => $stats['expiryTime'] ?? $stats['expiry_time'] ?? $this->expiry_time,
            'total_gb_bytes' => $stats['totalGB'] ?? $stats['total'] ?? $this->total_gb_bytes,
            'reset' => $stats['reset'] ?? $this->reset,
            'last_traffic_sync_at' => now(),
        ]);

        $this->updateTrafficUsagePercentage();
    }

    /**
     * Calculate and update traffic usage percentage
     */
    public function updateTrafficUsagePercentage(): void
    {
        if ($this->total_gb_bytes > 0) {
            $this->traffic_percentage_used = ($this->remote_total / $this->total_gb_bytes) * 100;
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
            'reset' => $this->reset + 1,
            'last_traffic_sync_at' => now(),
        ]);

        $this->updateTrafficUsagePercentage();
    }

    /**
     * Generate UUID if missing (required for 3X-UI)
     */
    public function generateUuidIfMissing(): void
    {
        if (empty($this->id) || !Str::isUuid($this->id)) {
            $this->id = (string) Str::uuid();
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
     * Generate email if missing
     */
    public function generateEmailIfMissing(): void
    {
        if (empty($this->email)) {
            $this->email = Str::lower(Str::random(8));
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
        $this->reset = $this->reset ?? 0;
        $this->expiry_time = $this->expiry_time ?? 0;
    }
}
