<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServerPlan extends Model
{
    use HasFactory;

    protected $table = 'server_plans';

    protected $fillable = [
        'server_id',
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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'in_stock' => 'boolean',
        'on_sale' => 'boolean',
        'auto_provision' => 'boolean',
        'renewable' => 'boolean',
        'provision_settings' => 'array',
        'performance_metrics' => 'array',
        'price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'data_limit_gb' => 'decimal:2',
    ];

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

    public function serverClients(): HasMany
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
}
