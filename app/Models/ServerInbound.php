<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerInbound extends Model
{
    use HasFactory;

    protected $table = 'server_inbounds';

    protected $fillable = [
        'server_id',

        // Core 3X-UI inbound fields
        'remote_id',                // 3X-UI inbound ID
        'tag',                      // 3X-UI inbound tag identifier
        'listen',                   // 3X-UI binding IP address
        'port',                     // 3X-UI inbound port
        'protocol',                 // 3X-UI protocol (vless, vmess, trojan, etc.)
        'enable',                   // 3X-UI inbound enabled status
        'remark',                   // 3X-UI inbound remark/name
        'expiry_time',              // 3X-UI expiry timestamp (milliseconds)

        // Traffic monitoring (3X-UI uses bytes)
        'up',                       // Upload traffic bytes
        'down',                     // Download traffic bytes
        'total',                    // Total traffic bytes
        'remote_up',                // 3X-UI remote upload bytes
        'remote_down',              // 3X-UI remote download bytes
        'remote_total',             // 3X-UI remote total bytes

        // 3X-UI JSON configuration fields (stored as TEXT for API compatibility)
        'settings',                 // 3X-UI settings JSON string
        'streamSettings',           // 3X-UI streamSettings JSON string
        'sniffing',                 // 3X-UI sniffing JSON string
        'allocate',                 // 3X-UI allocate JSON string

        // API synchronization tracking
        'last_api_sync_at',
        'api_sync_status',
        'api_sync_error',
        'api_sync_log',
        'last_traffic_sync_at',

        // Local management fields
        'capacity',                 // Maximum client capacity
        'current_clients',          // Current active clients
        'is_default',               // Default inbound for new clients
        'provisioning_enabled',     // Auto-provisioning enabled
        'status',                   // Local status (active, inactive, error)
        'status_message',           // Local status message
        'performance_metrics',
        'traffic_limit_bytes',
        'traffic_used_bytes',
        'client_template',
        'provisioning_rules',
        'last_sync_at',
    ];

    protected $casts = [
        // Core 3X-UI fields
        'remote_id' => 'integer',           // 3X-UI inbound ID
        'port' => 'integer',                // 3X-UI inbound port
        'enable' => 'boolean',              // 3X-UI inbound enabled status
        'expiry_time' => 'integer',         // 3X-UI expiry timestamp (milliseconds)

        // Traffic data (bytes) - 3X-UI uses big integers
        'up' => 'integer',
        'down' => 'integer',
        'total' => 'integer',
        'remote_up' => 'integer',
        'remote_down' => 'integer',
        'remote_total' => 'integer',

        // Boolean flags
        'is_default' => 'boolean',
        'provisioning_enabled' => 'boolean',

        // Timestamps
        'last_sync_at' => 'datetime',
        'last_api_sync_at' => 'datetime',
        'last_traffic_sync_at' => 'datetime',

        // JSON arrays (for internal use - parsed versions)
        'performance_metrics' => 'array',
        'traffic_limit_bytes' => 'integer',
        'traffic_used_bytes' => 'integer',
        'client_template' => 'array',
        'provisioning_rules' => 'array',
        'api_sync_log' => 'array',
        'capacity' => 'integer',
        'current_clients' => 'integer',

        // Note: settings, streamSettings, sniffing, allocate are stored as TEXT (JSON strings)
    // for 3X-UI API compatibility - but in our schema they are JSON columns so we cast them
    // to arrays to allow factories and code to assign arrays directly without conversion errors.
    'settings' => 'array',
    'streamSettings' => 'array',
    'sniffing' => 'array',
    'allocate' => 'array',
    ];

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class, 'server_inbound_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function serverPlans(): HasMany
    {
        return $this->hasMany(ServerPlan::class, 'preferred_inbound_id');
    }

    // Enhanced provisioning methods

    /**
     * Check if this inbound can provision new clients
     */
    public function canProvision(int $quantity = 1): bool
    {
        if (!$this->provisioning_enabled || !$this->enable || $this->status !== 'active') {
            return false;
        }

        // Check capacity
        if ($this->capacity !== null) {
            return ($this->current_clients + $quantity) <= $this->capacity;
        }

        return true; // Unlimited capacity
    }

    /**
     * Get available capacity
     */
    public function getAvailableCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null; // Unlimited
        }

        return max(0, $this->capacity - $this->current_clients);
    }

    /**
     * Get capacity utilization percentage
     */
    public function getCapacityUtilization(): float
    {
        if ($this->capacity === null) {
            return 0; // Unlimited capacity
        }

        if ($this->capacity === 0) {
            return 100; // No capacity
        }

        return ($this->current_clients / $this->capacity) * 100;
    }

    /**
     * Increment current clients count
     */
    public function incrementClients(int $count = 1): void
    {
        $this->increment('current_clients', $count);

        // Update status if at capacity
        if ($this->capacity && $this->current_clients >= $this->capacity) {
            $this->update(['status' => 'full']);
        }
    }

    /**
     * Decrement current clients count
     */
    public function decrementClients(int $count = 1): void
    {
        $this->decrement('current_clients', max(0, $count));

        // Update status if no longer full
        if ($this->status === 'full' && $this->canProvision()) {
            $this->update(['status' => 'active']);
        }
    }

    /**
     * Get client template with defaults
     */
    public function getClientTemplate(): array
    {
        $defaults = [
            'flow' => 'xtls-rprx-vision',
            'limit_ip' => 2,
            'security' => 'reality',
            'network' => 'tcp',
            'header_type' => 'none',
        ];

        return array_merge($defaults, $this->client_template ?? []);
    }

    /**
     * Update traffic statistics
     */
    public function updateTrafficStats(int $up, int $down): void
    {
        $this->update([
            'up' => $up,
            'down' => $down,
            'traffic_used_bytes' => $up + $down,
        ]);
    }

    /**
     * Check if traffic limit is exceeded
     */
    public function isTrafficLimitExceeded(): bool
    {
        if ($this->traffic_limit_bytes === null) {
            return false;
        }

        return $this->traffic_used_bytes >= $this->traffic_limit_bytes;
    }

    /**
     * Update performance metrics
     */
    public function updatePerformanceMetrics(array $metrics): void
    {
        $current = $this->performance_metrics ?? [];
        $updated = array_merge($current, $metrics);
        $updated['updated_at'] = now()->toISOString();

        $this->update(['performance_metrics' => $updated]);
    }

    /**
     * Sync with remote XUI panel
     */
    public function syncWithRemote(): bool
    {
        try {
            $xuiService = new \App\Services\XUIService($this->server_id);
            $remoteInbound = collect($xuiService->getInbounds())
                ->firstWhere('port', $this->port);

            if ($remoteInbound) {
                $this->update([
                    'up' => $remoteInbound->up ?? $this->up,
                    'down' => $remoteInbound->down ?? $this->down,
                    'enable' => $remoteInbound->enable ?? $this->enable,
                    'last_sync_at' => now(),
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error("Failed to sync inbound {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create or update a ServerInbound from a remote inbound object.
     *
     * @param object $inbound
     * @param int $serverId
     * @return ServerInbound
     */
    public static function fromRemoteInbound(object $inbound, int $serverId): self
    {
        $settings = json_decode($inbound->settings ?? '{}', true);
        $clients = $settings['clients'] ?? [];

        return self::updateOrCreate(
            [
                'server_id' => $serverId,
                'port' => $inbound->port,
            ],
            [
                'remote_id' => $inbound->id ?? ($inbound->remote_id ?? null),
                'protocol' => $inbound->protocol ?? null,
                'remark' => $inbound->remark ?? '',
                'enable' => $inbound->enable ?? true,
                'expiry_time' => $inbound->expiry_time ?? 0,  // Store milliseconds directly from 3X-UI API
                'settings' => $settings,
                'streamSettings' => is_string($inbound->streamSettings) ? json_decode($inbound->streamSettings, true) : (array) $inbound->streamSettings,
                'sniffing' => json_decode($inbound->sniffing ?? '{}', true),
                'allocate' => json_decode($inbound->allocate ?? '{}', true),
                'up' => $inbound->up ?? 0,
                'down' => $inbound->down ?? 0,
                'total' => count($clients), // ✅ set total to number of clients
            ]
        );
    }

    // === 3X-UI API INTEGRATION METHODS ===

    /**
     * Create inbound data for 3X-UI API (add/update operations)
     */
    public function toXuiApiData(): array
    {
        return [
            'up' => $this->remote_up ?? 0,
            'down' => $this->remote_down ?? 0,
            'total' => $this->remote_total ?? 0,
            'remark' => $this->remark ?? '',
            'enable' => $this->enable,
            'expiry_time' => $this->expiry_time ?? 0, // 3X-UI uses milliseconds timestamp
            'listen' => $this->listen ?? '',
            'port' => $this->port,
            'protocol' => $this->protocol,
            'settings' => $this->remote_settings ?? '{}',
            'streamSettings' => $this->remote_stream_settings ?? '{}',
            'tag' => $this->tag ?? "inbound-{$this->port}",
            'sniffing' => $this->remote_sniffing ?? '{}',
            'allocate' => $this->remote_allocate ?? '{}',
        ];
    }

    /**
     * Update inbound from 3X-UI API response data
     */
    public function updateFromXuiApiData(array $data): void
    {
        $this->fill([
            'remote_id' => $data['id'] ?? null,
            'up' => $data['up'] ?? 0,
            'down' => $data['down'] ?? 0,
            'total' => $data['total'] ?? 0,
            'remote_up' => $data['up'] ?? 0,
            'remote_down' => $data['down'] ?? 0,
            'remote_total' => $data['total'] ?? 0,
            'remark' => $data['remark'] ?? '',
            'enable' => $data['enable'] ?? true,
            'expiry_time' => $data['expiry_time'] ?? 0,
            'listen' => $data['listen'] ?? '',
            'port' => $data['port'] ?? $this->port,
            'protocol' => $data['protocol'] ?? $this->protocol,
            'tag' => $data['tag'] ?? $this->tag,
            'remote_settings' => $data['settings'] ?? null,
            'remote_stream_settings' => $data['streamSettings'] ?? null,
            'remote_sniffing' => $data['sniffing'] ?? null,
            'remote_allocate' => $data['allocate'] ?? null,
            'last_api_sync_at' => now(),
            'api_sync_status' => 'success',
            'api_sync_error' => null,
        ]);

        // Parse and update internal arrays for easier access
        $this->parseRemoteSettingsToLocal();

        // Sync client statistics if provided
        if (isset($data['clientStats']) && is_array($data['clientStats'])) {
            $this->syncClientsFromApiData($data['clientStats']);
        }
    }

    /**
     * Parse remote JSON settings to local arrays for easier access
     */
    protected function parseRemoteSettingsToLocal(): void
    {
        try {
            if ($this->remote_settings) {
                $this->settings = json_decode($this->remote_settings, true);
            }
            if ($this->remote_stream_settings) {
                $this->streamSettings = json_decode($this->remote_stream_settings, true);
            }
            if ($this->remote_sniffing) {
                $this->sniffing = json_decode($this->remote_sniffing, true);
            }
            if ($this->remote_allocate) {
                $this->allocate = json_decode($this->remote_allocate, true);
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to parse remote settings for inbound {$this->id}: " . $e->getMessage());
        }
    }

    /**
     * Sync clients from 3X-UI API clientStats data
     */
    protected function syncClientsFromApiData(array $clientStats): void
    {
        foreach ($clientStats as $clientData) {
            $client = $this->clients()->where('email', $clientData['email'])->first();

            if ($client) {
                $client->updateFromXuiApiClientStats($clientData);
                $client->save();
            }
        }
    }

    /**
     * Check if this inbound is synchronized with 3X-UI
     */
    public function isSyncedWithXui(): bool
    {
        return !empty($this->remote_id) &&
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
     * Get clients that need to be synced with 3X-UI
     */
    public function getClientsNeedingSync(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->clients()
            ->where(function ($query) {
                $query->where('api_sync_status', '!=', 'success')
                      ->orWhere('last_api_sync_at', '<', now()->subHour())
                      ->orWhereNull('last_api_sync_at');
            })
            ->get();
    }
}
