<?php

namespace App\Models;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'grand_amount',
        'currency',
        'payment_method',
        'payment_status',
        'order_status',
        'payment_invoice_url',
        'notes',
    ];

    public function markAsPaid(string $url): void
    {
        $this->update([
            'payment_status' => 'paid',
            'order_status' => 'processing',
            'payment_invoice_url' => $url,
        ]);

        $this->invoice()?->update(['invoice_url' => $url]);
    }

    public function markAsProcessing(string $url): void
    {
        $this->update([
            'payment_status' => 'pending',
            'order_status' => 'processing',
            'payment_invoice_url' => $url,
        ]);

        $this->invoice()?->update(['invoice_url' => $url]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['order_status' => 'completed']);
    }

    public function updateStatus(string $status): void
    {
        $allowed = ['new', 'processing', 'completed', 'dispute'];

        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Invalid order status: {$status}");
        }

        $this->update(['order_status' => $status]);
    }

    public function setStatus(string $status): static
    {
        $this->updateStatus($status);
        return $this;
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method');
    }

    // Enhanced relationships for XUI integration

    public function serverClients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServerClient::class, 'order_server_clients')
                    ->withPivot(['provision_status', 'provision_error', 'provision_attempts'])
                    ->withTimestamps();
    }

    public function orderServerClients(): HasMany
    {
        return $this->hasMany(OrderServerClient::class);
    }

    // Enhanced order methods

    /**
     * Get provisioning status summary
     */
    public function getProvisioningStatus(): array
    {
        $provisions = $this->orderServerClients;

        return [
            'total' => $provisions->count(),
            'pending' => $provisions->where('provision_status', 'pending')->count(),
            'provisioning' => $provisions->where('provision_status', 'provisioning')->count(),
            'completed' => $provisions->where('provision_status', 'completed')->count(),
            'failed' => $provisions->where('provision_status', 'failed')->count(),
            'cancelled' => $provisions->where('provision_status', 'cancelled')->count(),
        ];
    }

    /**
     * Check if order is fully provisioned
     */
    public function isFullyProvisioned(): bool
    {
        $status = $this->getProvisioningStatus();
        return $status['total'] > 0 && $status['completed'] === $status['total'];
    }

    /**
     * Check if order has failed provisions
     */
    public function hasFailedProvisions(): bool
    {
        return $this->orderServerClients()->where('provision_status', 'failed')->exists();
    }

    /**
     * Get all clients created for this order
     */
    public function getAllClients(): \Illuminate\Database\Eloquent\Collection
    {
        return ServerClient::where('order_id', $this->id)->get();
    }

    /**
     * Get downloadable configuration for all clients
     */
    public function getClientConfigurations(): array
    {
        return $this->getAllClients()->map(function ($client) {
            return $client->getDownloadableConfig();
        })->toArray();
    }
}
