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
    use \App\Traits\LogsActivity;


    protected static function booted(): void
    {
        static::updated(function(self $order) {
            if ($order->isDirty('payment_status') && $order->payment_status === 'paid') {
                event(new \App\Events\OrderPaid($order));
            }
        });
    // (No creation mutation now; constraint dropped by migration if present)
    }
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
    // Legacy staff management field (not used for customer placement)
    'user_id',
        'order_number',
        'grand_amount',
        'currency',
        'payment_method',
        'payment_status',
        'order_status',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'billing_first_name',
        'billing_last_name',
        'billing_email',
        'billing_phone',
        'billing_company',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'coupon_code',
        'payment_transaction_id',
        'payment_invoice_url',
    'payment_details',
        'notes',
    ];

    protected $casts = [
        'payment_details' => 'array',
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

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class); // alias for legacy tests
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method');
    }

    /**
     * Backward compatibility accessor: some legacy services still reference $order->user_id.
     * Business rule moved to customers; expose customer_id via user_id attribute when accessed.
     */
    public function getUserIdAttribute(): ?int
    {
        if (array_key_exists('user_id', $this->attributes) && !is_null($this->attributes['user_id'])) {
            return (int) $this->attributes['user_id'];
        }
        return $this->customer_id; // fallback
    }

    /**
     * Assign (or update) the staff user managing this order (e.g. refund, support action).
     */
    public function assignManager(\App\Models\User $user): void
    {
    $this->forceFill(['user_id' => $user->id])->saveQuietly();
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
