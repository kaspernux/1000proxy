<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;
use App\Models\DownloadableItem;
use App\Models\ServerInfo;

class Server extends Model
{
    use HasFactory;

    protected $table = 'servers';

    protected $fillable = [
        'name',
        'username',
        'password',
        'server_category_id',
        'server_brand_id',
        'country',
        'flag',
        'description',
        'status',
        'host',
        'panel_port',
        'web_base_path',
        'panel_url',
        'ip',
        'port',
        'sni',
        'header_type',
        'request_header',
        'response_header',
        'security',
        'tlsSettings',
        'type',
        'port_type',
        'reality',
        'xui_config',
        'connection_settings',
        'last_connected_at',
        'health_status',
        'performance_metrics',
        'total_clients',
        'active_clients',
        'total_traffic_mb',
        'auto_provisioning',
        'max_clients_per_inbound',
        'provisioning_rules',
        'last_health_check_at',
        'health_message',
        'alert_settings',
        // 3X-UI session and API management fields
        'session_cookie',
        'session_expires_at',
        'last_login_at',
        'login_attempts',
        'last_login_attempt_at',
        'api_version',
        'web_base_path',
        'api_capabilities',
        'api_timeout',
        'api_retry_count',
        'api_rate_limits',
        'global_traffic_stats',
        'total_inbounds',
        'active_inbounds',
        'total_online_clients',
        'last_global_sync_at',
        'auto_sync_enabled',
        'sync_interval_minutes',
        'auto_cleanup_depleted',
        'backup_notifications_enabled',
        'monitoring_thresholds',
    ];

    protected $casts = [
        'tlsSettings' => 'array',
        'xui_config' => 'array',
        'connection_settings' => 'array',
        'performance_metrics' => 'array',
        'provisioning_rules' => 'array',
        'alert_settings' => 'array',
        'api_capabilities' => 'array',
        'api_rate_limits' => 'array',
        'global_traffic_stats' => 'array',
        'monitoring_thresholds' => 'array',
        'last_connected_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'session_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_login_attempt_at' => 'datetime',
        'last_global_sync_at' => 'datetime',
        'auto_provisioning' => 'boolean',
        'auto_sync_enabled' => 'boolean',
        'auto_cleanup_depleted' => 'boolean',
        'backup_notifications_enabled' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServerCategory::class, 'server_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ServerBrand::class, 'server_brand_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ServerPlan::class);
    }

    public function giftLists(): HasMany
    {
        return $this->hasMany(GiftList::class);
    }

    public function serverReviews(): HasMany
    {
        return $this->hasMany(ServerReview::class);
    }

    public function serverRatings(): HasMany
    {
        return $this->hasMany(ServerRating::class, 'server_id');
    }

    public function averageRating(): float
    {
        return $this->serverRatings()->avg('rating') ?? 0.0;
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function info(): HasOne
    {
        return $this->hasOne(ServerInfo::class, 'server_id');
    }

    public function inbounds(): HasMany
    {
        return $this->hasMany(ServerInbound::class, 'server_id');
    }

    public function downloadableItems(): HasMany
    {
        return $this->hasMany(DownloadableItem::class, 'server_id');
    }


    /**
     * Get all clients for the server via inbounds (hasManyThrough)
     */
    public function clients()
    {
        return $this->hasManyThrough(
            \App\Models\ServerClient::class,
            \App\Models\ServerInbound::class,
            'server_id', // Foreign key on server_inbounds table...
            'server_inbound_id', // Foreign key on server_clients table...
            'id', // Local key on servers table...
            'id'  // Local key on server_inbounds table...
        );
    }

    // Enhanced server management methods

    /**
     * Get default inbound for provisioning
     */
    public function getDefaultInbound(): ?ServerInbound
    {
        return $this->inbounds()
            ->where('is_default', true)
            ->where('provisioning_enabled', true)
            ->where('status', 'active')
            ->first() ?: $this->inbounds()
                ->where('provisioning_enabled', true)
                ->where('status', 'active')
                ->orderBy('current_clients', 'asc')
                ->first();
    }

    /**
     * Get best inbound for provisioning with capacity check
     */
    public function getBestInboundForProvisioning(int $quantity = 1): ?ServerInbound
    {
        return $this->inbounds()
            ->where('provisioning_enabled', true)
            ->where('status', 'active')
            ->where(function ($query) use ($quantity) {
                $query->whereNull('capacity')
                      ->orWhereRaw('current_clients + ? <= capacity', [$quantity]);
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('current_clients', 'asc')
            ->first();
    }

    /**
     * Check server health
     */
    public function checkHealth(): bool
    {
        try {
            $xuiService = new \App\Services\XUIService($this);
            $inbounds = $xuiService->listInbounds();

            $this->update([
                'health_status' => 'healthy',
                'last_connected_at' => now(),
                'last_health_check_at' => now(),
                'health_message' => 'Connection successful',
            ]);

            return true;
        } catch (\Exception $e) {
            $this->update([
                'health_status' => 'critical',
                'last_health_check_at' => now(),
                'health_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update server statistics
     */
    public function updateStatistics(): void
    {
        $totalClients = $this->inbounds()->sum('current_clients');
        $activeClients = ServerClient::whereHas('inbound', function ($query) {
            $query->where('server_id', $this->id);
        })->where('status', 'active')->count();

        $totalTrafficMb = ServerClient::whereHas('inbound', function ($query) {
            $query->where('server_id', $this->id);
        })->sum('traffic_used_mb');

        $this->update([
            'total_clients' => $totalClients,
            'active_clients' => $activeClients,
            'total_traffic_mb' => $totalTrafficMb,
        ]);
    }

    /**
     * Check if server can provision new clients
     */
    public function canProvision(int $quantity = 1): bool
    {
        if (!$this->auto_provisioning || $this->status !== 'up' || $this->health_status === 'critical') {
            return false;
        }

        return $this->getBestInboundForProvisioning($quantity) !== null;
    }

    /**
     * Get total available capacity across all inbounds
     */
    public function getTotalAvailableCapacity(): ?int
    {
        $inbounds = $this->inbounds()
            ->where('provisioning_enabled', true)
            ->where('status', 'active')
            ->get();

        if ($inbounds->contains(fn($inbound) => $inbound->capacity === null)) {
            return null; // Unlimited capacity
        }

        return $inbounds->sum(fn($inbound) => $inbound->getAvailableCapacity() ?? 0);
    }

    /**
     * Get provisioning rules with defaults
     */
    public function getProvisioningRules(): array
    {
        $defaults = [
            'max_clients_per_inbound' => $this->max_clients_per_inbound ?? 100,
            'auto_distribute_load' => true,
            'prefer_default_inbound' => true,
            'enable_health_checks' => true,
            'traffic_monitoring' => true,
        ];

        return array_merge($defaults, $this->provisioning_rules ?? []);
    }

    /**
     * Sync all inbounds with remote XUI panel
     */
    public function syncInbounds(): array
    {
        $results = [];

        foreach ($this->inbounds as $inbound) {
            $results[$inbound->id] = $inbound->syncWithRemote();
        }

        $this->updateStatistics();
        return $results;
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

    // URL helper methods (existing)
    public function getPanelHost(): ?string
    {
        return parse_url($this->panel_url, PHP_URL_HOST);
    }

    public function getPanelPort(): ?int
    {
        return parse_url($this->panel_url, PHP_URL_PORT) ?? 443;
    }

    public function getPanelWebPath(): string
    {
        $path = parse_url($this->panel_url, PHP_URL_PATH) ?? '';
        return rtrim($path, '/'); // strip trailing slash
    }

    public function getPanelBase(): string
    {
        $scheme = parse_url($this->panel_url, PHP_URL_SCHEME) ?? 'http';
        $host = $this->getPanelHost();
        $port = $this->getPanelPort();
        $web = $this->getPanelWebPath();

        return "{$scheme}://{$host}:{$port}{$web}";
    }

    public function getSubscriptionPort(): int
    {
        return ($this->getPanelPort() ?? 443) - 1;
    }

    // 3X-UI Session and API Management Methods

    /**
     * Check if the current session is valid
     */
    public function hasValidSession(): bool
    {
        return $this->session_cookie &&
               $this->session_expires_at &&
               $this->session_expires_at > now();
    }

    /**
     * Update session information after login
     */
    public function updateSession(string $sessionCookie, int $expiresInMinutes = 60): void
    {
        $this->update([
            'session_cookie' => $sessionCookie,
            'session_expires_at' => now()->addMinutes($expiresInMinutes),
            'last_login_at' => now(),
            'login_attempts' => 0, // Reset on successful login
        ]);
    }

    /**
     * Clear session information
     */
    public function clearSession(): void
    {
        $this->update([
            'session_cookie' => null,
            'session_expires_at' => null,
        ]);
    }

    /**
     * Increment login attempts
     */
    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
        $this->update(['last_login_attempt_at' => now()]);
    }

    /**
     * Check if login attempts are exceeded
     */
    public function isLoginLocked(): bool
    {
        return $this->login_attempts >= 5 &&
               $this->last_login_attempt_at &&
               now()->diffInMinutes($this->last_login_attempt_at) < 30;
    }

    /**
     * Get 3X-UI API base URL following the structure: http://{{HOST}}:{{PORT}}{{WEBBASEPATH}}/
     */
    public function getApiBaseUrl(): string
    {
        // If host and panel_port are set, use new structure
        if ($this->host && $this->panel_port) {
            $protocol = (strpos($this->host, 'https://') === 0) ? '' : 'http://';
            $host = ltrim($this->host, 'http://');
            $host = ltrim($host, 'https://');

            $url = $protocol . $host . ':' . $this->panel_port;

            // Add web base path if present
            $basePath = trim($this->web_base_path ?? '', '/');
            if ($basePath) {
                $url .= '/' . $basePath;
            }

            return $url;
        }

        // Fallback to old panel_url structure for backward compatibility
        $url = rtrim($this->panel_url ?? '', '/');
        $basePath = trim($this->web_base_path ?? '', '/');

        if ($basePath) {
            $url .= '/' . $basePath;
        }

        return $url;
    }

    /**
     * Get API endpoint URL
     */
    public function getApiEndpoint(string $endpoint): string
    {
        $baseUrl = $this->getApiBaseUrl();
        $endpoint = ltrim($endpoint, '/');

        return $baseUrl . '/' . $endpoint;
    }

    /**
     * Get session cookie header for API requests
     */
    public function getSessionHeader(): array
    {
        return [
            'Cookie' => 'session=' . $this->session_cookie,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Update global statistics from 3X-UI
     */
    public function updateGlobalStats(array $stats): void
    {
        $this->update([
            'global_traffic_stats' => $stats,
            'total_inbounds' => $stats['total_inbounds'] ?? $this->total_inbounds,
            'active_inbounds' => $stats['active_inbounds'] ?? $this->active_inbounds,
            'total_online_clients' => $stats['total_online_clients'] ?? $this->total_online_clients,
            'last_global_sync_at' => now(),
        ]);
    }

    /**
     * Check if auto sync is enabled and due
     */
    public function isAutoSyncDue(): bool
    {
        if (!$this->auto_sync_enabled) {
            return false;
        }

        if (!$this->last_global_sync_at) {
            return true; // Never synced
        }

        $intervalMinutes = $this->sync_interval_minutes ?? 5;
        return now()->diffInMinutes($this->last_global_sync_at) >= $intervalMinutes;
    }

    /**
     * Get API timeout in seconds
     */
    public function getApiTimeout(): int
    {
        return $this->api_timeout ?? 30;
    }

    /**
     * Get API retry count
     */
    public function getApiRetryCount(): int
    {
        return $this->api_retry_count ?? 3;
    }

    /**
     * Check if a specific API capability is supported
     */
    public function supportsApiCapability(string $capability): bool
    {
        $capabilities = $this->api_capabilities ?? [];
        return in_array($capability, $capabilities);
    }

    /**
     * Update API capabilities after discovery
     */
    public function updateApiCapabilities(array $capabilities): void
    {
        $this->update([
            'api_capabilities' => $capabilities,
        ]);
    }

    /**
     * Get health status for monitoring
     */
    public function getDetailedHealthStatus(): array
    {
        return [
            'server_status' => $this->status,
            'health_status' => $this->health_status,
            'has_valid_session' => $this->hasValidSession(),
            'last_login' => $this->last_login_at ? $this->last_login_at->format('c') : null,
            'last_sync' => $this->last_global_sync_at ? $this->last_global_sync_at->format('c') : null,
            'login_attempts' => $this->login_attempts,
            'is_login_locked' => $this->isLoginLocked(),
            'auto_sync_enabled' => $this->auto_sync_enabled,
            'is_sync_due' => $this->isAutoSyncDue(),
            'total_inbounds' => $this->total_inbounds,
            'active_inbounds' => $this->active_inbounds,
            'total_online_clients' => $this->total_online_clients,
            'api_version' => $this->api_version,
            'api_capabilities' => $this->api_capabilities ?? [],
        ];
    }

    /**
     * Check if server should cleanup depleted clients automatically
     */
    public function shouldAutoCleanupDepleted(): bool
    {
        return $this->auto_cleanup_depleted ?? false;
    }

    /**
     * Check if backup notifications are enabled
     */
    public function hasBackupNotificationsEnabled(): bool
    {
        return $this->backup_notifications_enabled ?? false;
    }

    /**
     * Get monitoring thresholds
     */
    public function getMonitoringThresholds(): array
    {
        return $this->monitoring_thresholds ?? [
            'cpu_usage_warning' => 80,
            'cpu_usage_critical' => 95,
            'memory_usage_warning' => 80,
            'memory_usage_critical' => 95,
            'disk_usage_warning' => 80,
            'disk_usage_critical' => 95,
            'client_count_warning' => 1000,
            'client_count_critical' => 1500,
            'traffic_gb_warning' => 1000,
            'traffic_gb_critical' => 2000,
        ];
    }

    /**
     * Update monitoring thresholds
     */
    public function updateMonitoringThresholds(array $thresholds): void
    {
        $this->update([
            'monitoring_thresholds' => array_merge($this->getMonitoringThresholds(), $thresholds),
        ]);
    }

    /**
     * Get sync interval in minutes
     */
    public function getSyncIntervalMinutes(): int
    {
        return $this->sync_interval_minutes ?? 5;
    }

    /**
     * Get full panel URL for display purposes
     */
    public function getFullPanelUrl(): string
    {
        return $this->getApiBaseUrl();
    }

    /**
     * Get panel access URL (without API paths)
     */
    public function getPanelAccessUrl(): string
    {
        if ($this->host && $this->panel_port) {
            $protocol = (strpos($this->host, 'https://') === 0) ? '' : 'http://';
            $host = ltrim($this->host, 'http://');
            $host = ltrim($host, 'https://');

            $url = $protocol . $host . ':' . $this->panel_port;

            // Add web base path if present
            $basePath = trim($this->web_base_path ?? '', '/');
            if ($basePath) {
                $url .= '/' . $basePath;
            }

            return $url;
        }

        return $this->panel_url ?? '';
    }
}
