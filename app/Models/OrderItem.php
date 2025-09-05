<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';


    protected $fillable = [
        'order_id',
        'server_plan_id',
        'quantity',
        'unit_amount',
        'total_amount',
        'agent_bought',
        'expires_at',
        'provisioning_summary',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    'provisioning_summary' => 'array',
    ];

    protected $appends = [
        // Make server accessible in JSON payloads similarly to legacy use
        'server',
    ];

    /**
     * Virtual attribute to map ToggleColumn 'auto_renew' to provisioning_summary[auto_renew_enabled].
     * This prevents Eloquent from trying to persist a non-existent 'auto_renew' column.
     */
    public function getAutoRenewAttribute(): bool
    {
        $sum = $this->provisioning_summary ?? [];
        return (bool) ($sum['auto_renew_enabled'] ?? false);
    }

    public function setAutoRenewAttribute($value): void
    {
    $sum = $this->provisioning_summary ?? [];
    $sum['auto_renew_enabled'] = (bool) $value;
    $this->provisioning_summary = $sum; // casted to array/json by Eloquent
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function serverPlan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class);
    }

    public function orderServerClients(): HasMany
    {
        return $this->hasMany(OrderServerClient::class);
    }

    public function serverClients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServerClient::class, 'order_server_clients', 'order_item_id', 'server_client_id')
                    ->withPivot(['provision_status', 'provision_error', 'provision_attempts'])
                    ->withTimestamps();
    }

    /**
     * Virtual accessor to the related Server model via the ServerPlan relationship.
     * This is NOT a true Eloquent relationship (so it cannot be eager loaded directly as items.server),
     * but provides convenient $orderItem->server usage in UI code.
     */
    public function getServerAttribute(): ?Server
    {
        return $this->serverPlan?->server;
    }

    /**
     * Virtual accessor returning the first associated ServerClient (if any) for backward compatibility
     * with legacy code referencing $orderItem->server_client. For multiple clients, consider using
     * the serverClients() relation directly.
     */
    public function getServerClientAttribute(): ?ServerClient
    {
        return $this->serverClients()->first();
    }

    // Enhanced methods

    /**
     * Get QR codes for this order item
     */
    public function getQrCodes(): array
    {
        $client = ServerClient::where('order_id', $this->order_id)
            ->where('plan_id', $this->server_plan_id)
            ->first();

        if (!$client) {
            // Fallback to old method for backward compatibility
            $client = ServerClient::query()
                ->where('plan_id', $this->server_plan_id)
                ->where('email', 'LIKE', '%#ID ' . $this->order->customer_id)
                ->first();
        }

        return [
            'clientQr' => $client?->qr_code_client,
            'subQr' => $client?->qr_code_sub,
            'jsonQr' => $client?->qr_code_sub_json,
        ];
    }

    /**
     * Get all clients created for this order item
     */
    public function getClients(): \Illuminate\Database\Eloquent\Collection
    {
        return ServerClient::where('order_id', $this->order_id)
            ->where('plan_id', $this->server_plan_id)
            ->get();
    }

    /**
     * Get provisioning status for this order item
     */
    public function getProvisioningStatus(): array
    {
        $provisions = $this->orderServerClients;

        return [
            'total_requested' => $this->quantity,
            'total_provisions' => $provisions->count(),
            'pending' => $provisions->where('provision_status', 'pending')->count(),
            'provisioning' => $provisions->where('provision_status', 'provisioning')->count(),
            'completed' => $provisions->where('provision_status', 'completed')->count(),
            'failed' => $provisions->where('provision_status', 'failed')->count(),
            'cancelled' => $provisions->where('provision_status', 'cancelled')->count(),
        ];
    }

    /**
     * Check if this order item is fully provisioned
     */
    public function isFullyProvisioned(): bool
    {
        $status = $this->getProvisioningStatus();
        return $status['completed'] === $this->quantity;
    }

    /**
     * Get total amount including setup fees
     */
    public function getTotalAmountWithFees(): float
    {
        $setupFee = $this->serverPlan->setup_fee ?? 0;
        return $this->total_amount + ($setupFee * $this->quantity);
    }
}
