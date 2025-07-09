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
        'protocol',
        'port',
        'enable',
        'remark',
        'expiryTime',
        'settings',
        'streamSettings',
        'sniffing',
        'up',
        'down',
        'total',
        'allocate',
        'capacity',
        'current_clients',
        'is_default',
        'provisioning_enabled',
        'performance_metrics',
        'traffic_limit_bytes',
        'traffic_used_bytes',
        'client_template',
        'provisioning_rules',
        'last_sync_at',
        'status',
        'status_message',
        // 3X-UI API specific fields
        'tag',
        'listen',
        'remote_settings',
        'remote_stream_settings',
        'remote_sniffing',
        'remote_allocate',
        'remote_id',
        'last_api_sync_at',
        'api_sync_log',
        'api_sync_status',
        'api_sync_error',
        'remote_up',
        'remote_down',
        'remote_total',
        'last_traffic_sync_at',
    ];

    protected $casts = [
        'up' => 'integer',
        'down' => 'integer',
        'total' => 'integer',
        'remote_up' => 'integer',
        'remote_down' => 'integer',
        'remote_total' => 'integer',
        'enable' => 'boolean',
        'is_default' => 'boolean',
        'provisioning_enabled' => 'boolean',
        'expiryTime' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_api_sync_at' => 'datetime',
        'last_traffic_sync_at' => 'datetime',
        'clientStats' => 'array',
        'settings' => 'array',
        'streamSettings' => 'array',
        'sniffing' => 'array',
        'allocate' => 'array',
        'performance_metrics' => 'array',
        'client_template' => 'array',
        'provisioning_rules' => 'array',
        'api_sync_log' => 'array',
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
            'limitIp' => 2,
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
                'protocol' => $inbound->protocol ?? null,
                'remark' => $inbound->remark ?? '',
                'enable' => $inbound->enable ?? true,
                'expiryTime' => isset($inbound->expiryTime)
                    ? Carbon::createFromTimestampMs($inbound->expiryTime)
                    : null,
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

    // 3X-UI API Integration Methods

    /**
     * Get the 3X-UI settings as a JSON string (as expected by the API)
     */
    public function getRemoteSettingsAttribute(): ?string
    {
        return $this->attributes['remote_settings'] ?? null;
    }

    /**
     * Set the 3X-UI settings from a JSON string
     */
    public function setRemoteSettingsAttribute(?string $value): void
    {
        $this->attributes['remote_settings'] = $value;

        // Also update the parsed settings array for internal use
        if ($value) {
            try {
                $this->attributes['settings'] = json_decode($value, true);
            } catch (\Exception $e) {
                \Log::warning("Failed to parse remote_settings JSON for inbound {$this->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get the 3X-UI stream settings as a JSON string
     */
    public function getRemoteStreamSettingsAttribute(): ?string
    {
        return $this->attributes['remote_stream_settings'] ?? null;
    }

    /**
     * Set the 3X-UI stream settings from a JSON string
     */
    public function setRemoteStreamSettingsAttribute(?string $value): void
    {
        $this->attributes['remote_stream_settings'] = $value;

        // Also update the parsed streamSettings array for internal use
        if ($value) {
            try {
                $this->attributes['streamSettings'] = json_decode($value, true);
            } catch (\Exception $e) {
                \Log::warning("Failed to parse remote_stream_settings JSON for inbound {$this->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get the 3X-UI sniffing settings as a JSON string
     */
    public function getRemoteSniffingAttribute(): ?string
    {
        return $this->attributes['remote_sniffing'] ?? null;
    }

    /**
     * Set the 3X-UI sniffing settings from a JSON string
     */
    public function setRemoteSniffingAttribute(?string $value): void
    {
        $this->attributes['remote_sniffing'] = $value;

        // Also update the parsed sniffing array for internal use
        if ($value) {
            try {
                $this->attributes['sniffing'] = json_decode($value, true);
            } catch (\Exception $e) {
                \Log::warning("Failed to parse remote_sniffing JSON for inbound {$this->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get the 3X-UI allocate settings as a JSON string
     */
    public function getRemoteAllocateAttribute(): ?string
    {
        return $this->attributes['remote_allocate'] ?? null;
    }

    /**
     * Set the 3X-UI allocate settings from a JSON string
     */
    public function setRemoteAllocateAttribute(?string $value): void
    {
        $this->attributes['remote_allocate'] = $value;

        // Also update the parsed allocate array for internal use
        if ($value) {
            try {
                $this->attributes['allocate'] = json_decode($value, true);
            } catch (\Exception $e) {
                \Log::warning("Failed to parse remote_allocate JSON for inbound {$this->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Create inbound data for 3X-UI API (add/update operations)
     */
    public function toXuiApiData(): array
    {
        return [
            'up' => $this->up ?? 0,
            'down' => $this->down ?? 0,
            'total' => $this->total ?? 0,
            'remark' => $this->remark ?? '',
            'enable' => $this->enable,
            'expiryTime' => $this->expiryTime ? $this->expiryTime->timestamp * 1000 : 0, // 3X-UI uses milliseconds
            'listen' => $this->listen ?? '',
            'port' => $this->port,
            'protocol' => $this->protocol,
            'settings' => $this->remote_settings ?? '{}',
            'streamSettings' => $this->remote_stream_settings ?? '{}',
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
            'expiryTime' => isset($data['expiryTime']) && $data['expiryTime'] > 0
                ? Carbon::createFromTimestamp($data['expiryTime'] / 1000)
                : null,
            'listen' => $data['listen'] ?? '',
            'port' => $data['port'] ?? $this->port,
            'protocol' => $data['protocol'] ?? $this->protocol,
            'tag' => $data['tag'] ?? null,
            'remote_settings' => $data['settings'] ?? '{}',
            'remote_stream_settings' => $data['streamSettings'] ?? '{}',
            'remote_sniffing' => $data['sniffing'] ?? '{}',
            'remote_allocate' => $data['allocate'] ?? '{}',
            'last_api_sync_at' => now(),
            'api_sync_status' => 'success',
            'api_sync_error' => null,
        ]);

        // Update client statistics if provided
        if (isset($data['clientStats']) && is_array($data['clientStats'])) {
            $this->syncClientsFromApiData($data['clientStats']);
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
     * Get traffic usage percentage based on remote traffic data
     */
    public function getRemoteTrafficUsagePercentage(): float
    {
        if (!$this->traffic_limit_bytes || $this->traffic_limit_bytes <= 0) {
            return 0.0;
        }

        return min(100.0, ($this->remote_total / $this->traffic_limit_bytes) * 100);
    }

    /**
     * Check if inbound is over traffic limit
     */
    public function isOverTrafficLimit(): bool
    {
        return $this->traffic_limit_bytes > 0 && $this->remote_total >= $this->traffic_limit_bytes;
    }

    /**
     * Get online clients count from last sync
     */
    public function getOnlineClientsCount(): int
    {
        return $this->clients()->where('is_online', true)->count();
    }
}
