<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServerPlan extends Model
{
    use \App\Traits\LogsActivity;
    use HasFactory;

    protected $table = 'server_plans';

    protected $fillable = [
        'server_id',
        'server_brand_id',        // Brand relationship for filtering
        'server_category_id',     // Category relationship for filtering
        'name',
        'slug',
        'product_image',
        'description',
        'capacity',
        'price',
        'type',
        'days',
        'volume',
        'is_active',
        'is_featured',
        'in_stock',
        'on_sale',
        'preferred_inbound_id',
        'max_clients',
        'current_clients',
        'auto_provision',
        'provision_settings',
        'data_limit_gb',
        'concurrent_connections',
        'performance_metrics',
        'trial_days',
        'setup_fee',
        'renewable',
        // Advanced filtering fields
        'country_code',           // ISO country code for location filtering
        'region',                 // Region/state for location filtering
        'protocol',               // Protocol type for filtering
        'bandwidth_mbps',         // Bandwidth for performance filtering
        'supports_ipv6',          // IPv6 support for filtering
        'popularity_score',       // Popularity for sorting
        'server_status',          // Server status for filtering
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'in_stock' => 'boolean',
        'on_sale' => 'boolean',
        'auto_provision' => 'boolean',
        'renewable' => 'boolean',
        'supports_ipv6' => 'boolean',
        'provision_settings' => 'array',
        'performance_metrics' => 'array',
        'price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'data_limit_gb' => 'decimal:2',
        'popularity_score' => 'integer',
        'bandwidth_mbps' => 'integer',
    ];

    /**
     * Normalize plan provisioning type (returns 'shared' or 'dedicated').
     * Accept existing historical values: 'single' => dedicated, 'multiple' => shared.
     */
    public function getProvisioningTypeAttribute(): string
    {
        $raw = strtolower((string) ($this->type ?? 'shared'));
        return match($raw) {
            'single', 'dedicated' => 'dedicated',
            'multiple', 'shared' => 'shared',
            default => 'shared',
        };
    }

    /**
     * Convenience: is this a dedicated plan?
     */
    public function isDedicated(): bool
    {
        return $this->provisioning_type === 'dedicated';
    }

    /**
     * Convenience: is this a shared plan?
     */
    public function isShared(): bool
    {
        return $this->provisioning_type === 'shared';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ServerBrand::class, 'server_brand_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServerCategory::class, 'server_category_id');
    }

    public function preferredInbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'preferred_inbound_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class, 'plan_id');
    }

    // Enhanced provisioning methods

    /**
     * Check if this plan can accommodate new clients
     */
    public function hasCapacity(int $quantity = 1): bool
    {
        if ($this->max_clients === null) {
            return true; // Unlimited capacity
        }

        return ($this->current_clients + $quantity) <= $this->max_clients;
    }

    /**
     * Get the best inbound for provisioning clients
     */
    public function getBestInbound(): ?ServerInbound
    {
        // First try preferred inbound
        if ($this->preferred_inbound_id && $this->preferredInbound && $this->preferredInbound->canProvision()) {
            return $this->preferredInbound;
        }

        // Fall back to server's default inbound
        return $this->server->inbounds()
            ->where('provisioning_enabled', true)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('capacity')
                      ->orWhereRaw('current_clients < capacity');
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('current_clients', 'asc')
            ->first();
    }

    /**
     * Get total price including setup fee
     */
    public function getTotalPrice(): float
    {
        return (float) ($this->price + $this->setup_fee);
    }

    /**
     * Get provisioning settings with defaults
     */
    public function getProvisioningSettings(): array
    {
        $defaults = [
            'auto_activate' => true,
            'send_welcome_email' => true,
            'generate_qr_codes' => true,
            'enable_traffic_monitoring' => true,
            'connection_limit' => $this->concurrent_connections ?? 10,
            'data_limit_gb' => $this->data_limit_gb ?? $this->volume,
        ];

        return array_merge($defaults, $this->provision_settings ?? []);
    }

    /**
     * Check if plan is available for purchase
     */
    public function isAvailable(): bool
    {
        return $this->is_active &&
               $this->in_stock &&
               $this->on_sale &&
               $this->server->status === 'up' &&
               $this->server->auto_provisioning;
    }

    /**
     * Increment current clients count
     */
    public function incrementClients(int $count = 1): void
    {
        $this->increment('current_clients', $count);
    }

    /**
     * Decrement current clients count
     */
    public function decrementClients(int $count = 1): void
    {
        $this->decrement('current_clients', max(0, $count));
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

    // === ADVANCED FILTERING SCOPES ===

    /**
     * Filter by location (country/region)
     */
    public function scopeByLocation($query, ?string $countryCode = null, ?string $region = null)
    {
        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }

        if ($region) {
            $query->where('region', 'like', "%{$region}%");
        }

        return $query;
    }

    /**
     * Filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('server_category_id', $categoryId);
    }

    /**
     * Filter by brand
     */
    public function scopeByBrand($query, $brandId)
    {
        return $query->where('server_brand_id', $brandId);
    }

    /**
     * Filter by protocol
     */
    public function scopeByProtocol($query, $protocol)
    {
        return $query->where('protocol', $protocol);
    }

    /**
     * Filter by price range
     */
    public function scopeByPriceRange($query, ?float $minPrice = null, ?float $maxPrice = null)
    {
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Filter by bandwidth
     */
    public function scopeByBandwidth($query, ?int $minBandwidth = null)
    {
        if ($minBandwidth !== null) {
            $query->where('bandwidth_mbps', '>=', $minBandwidth);
        }

        return $query;
    }

    /**
     * Filter by IPv6 support
     */
    public function scopeWithIpv6($query, bool $requireIpv6 = true)
    {
        return $query->where('supports_ipv6', $requireIpv6);
    }

    /**
     * Filter by server status
     */
    public function scopeByStatus($query, $status = 'online')
    {
        return $query->where('server_status', $status);
    }

    /**
     * Sort by popularity
     */
    public function scopeByPopularity($query, string $direction = 'desc')
    {
        return $query->orderBy('popularity_score', $direction);
    }

    /**
     * Sort by price
     */
    public function scopeByPrice($query, string $direction = 'asc')
    {
        return $query->orderBy('price', $direction);
    }

    /**
     * Location-first sorting (as per TODO requirements)
     */
    public function scopeLocationFirstSort($query)
    {
        return $query->orderBy('country_code')
                    ->orderBy('region')
                    ->orderBy('server_category_id')
                    ->orderBy('server_brand_id')
                    ->orderBy('popularity_score', 'desc');
    }

    /**
     * Get available countries with server counts
     */
    public static function getAvailableCountries()
    {
        return static::select('country_code', \DB::raw('count(*) as plan_count'))
                    ->whereNotNull('country_code')
                    ->where('is_active', true)
                    ->where('server_status', 'online')
                    ->groupBy('country_code')
                    ->orderBy('country_code')
                    ->get();
    }

    /**
     * Get available categories with server counts
     */
    public static function getAvailableCategories()
    {
        return static::with('category')
                    ->select('server_category_id', \DB::raw('count(*) as plan_count'))
                    ->whereNotNull('server_category_id')
                    ->where('is_active', true)
                    ->where('server_status', 'online')
                    ->groupBy('server_category_id')
                    ->orderBy('plan_count', 'desc')
                    ->get();
    }

    /**
     * Get available brands with server counts
     */
    public static function getAvailableBrands()
    {
        return static::with('brand')
                    ->select('server_brand_id', \DB::raw('count(*) as plan_count'))
                    ->whereNotNull('server_brand_id')
                    ->where('is_active', true)
                    ->where('server_status', 'online')
                    ->groupBy('server_brand_id')
                    ->orderBy('plan_count', 'desc')
                    ->get();
    }
}
