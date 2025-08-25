<?php

namespace App\Models;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
            // After commit, ensure an invoice exists and reflect latest payment status
            DB::afterCommit(function() use ($order) {
                try {
                    $order->ensureInvoice();
                } catch (\Throwable $e) {
                    \Log::warning('ensureInvoice on order updated failed', ['order_id'=>$order->id,'error'=>$e->getMessage()]);
                }
            });
        });
        static::created(function(self $order) {
            // Defer until after surrounding transaction (e.g., Checkout) commits to avoid duplicate invoices
            DB::afterCommit(function() use ($order) {
                try {
                    $order->ensureInvoice();
                } catch (\Throwable $e) {
                    \Log::warning('ensureInvoice on order created failed', ['order_id'=>$order->id,'error'=>$e->getMessage()]);
                }
            });
        });
    // (No creation mutation now; constraint dropped by migration if present)
    }
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
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
        'total_amount' => 'decimal:2',
        'grand_amount' => 'decimal:2',
    ];

    // Ensure legacy attributes appear in serialized output for tests
    protected $appends = [
        'status',
        'server_id',
        'server',
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
    $this->update(['order_status' => 'completed', 'status' => 'completed']);
    }

    public function updateStatus(string $status): void
    {
        $allowed = ['new', 'processing', 'completed', 'dispute'];

        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Invalid order status: {$status}");
        }

    $this->update(['order_status' => $status, 'status' => $status]);
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

    

    /**
     * Accessor: normalize a single server_id by reading from first item plan's server.
     * Tests expect "server_id" field at the order level.
     */
    public function getServerIdAttribute(): ?int
    {
        $plan = $this->orderItems()->with('serverPlan')->first()?->serverPlan;
        return $plan?->server_id ? (int) $plan->server_id : null;
    }

    /**
     * Accessor: expose related server via first order item for convenience
     */
    public function getServerAttribute(): ?Server
    {
        return $this->orderItems()->with('serverPlan.server')->first()?->serverPlan?->server;
    }

    /**
     * Accessor: expose unified status; prefer modern 'status' column, fallback to legacy 'order_status'.
     */
    public function getStatusAttribute(): ?string
    {
        if (array_key_exists('status', $this->attributes) && !is_null($this->attributes['status']) && $this->attributes['status'] !== '') {
            return $this->attributes['status'];
        }
        return $this->attributes['order_status'] ?? null;
    }

    /**
     * Mutator: writing to virtual 'status' should update 'order_status' column.
     */
    public function setStatusAttribute($value): void
    {
    // Map virtual status writes to the modern 'status' column to avoid enum mismatch with order_status
    $this->attributes['status'] = $value;
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method');
    }

    /**
     * Create or update an invoice to ensure every order has one.
     */
    public function ensureInvoice(array $overrides = []): ?Invoice
    {
        try {
            $invoice = $this->invoice;
            // Resolve a payment method id robustly
            $pmId = null;
            try {
                if (is_numeric($this->payment_method)) {
                    $pmId = (int) $this->payment_method;
                }
                if (!$pmId) {
                    $pm = \App\Models\PaymentMethod::first();
                    if (!$pm) {
                        // Create a sensible default (Wallet) if table is empty
                        $pm = \App\Models\PaymentMethod::create([
                            'name' => 'Wallet',
                            'slug' => 'wallet',
                            'type' => 'wallet',
                            'is_active' => true,
                        ]);
                    }
                    $pmId = $pm?->id;
                }
            } catch (\Throwable $e) {
                \Log::warning('ensureInvoice: failed to resolve payment method, invoice may use null', ['order_id'=>$this->id,'error'=>$e->getMessage()]);
            }

            $base = [
                'customer_id' => $this->customer_id,
                'order_id' => $this->id,
                // Fallback to a valid PaymentMethod id to satisfy FK when null
                'payment_method_id' => $pmId ?: (\App\Models\PaymentMethod::first()?->id),
                'price_amount' => (string) ($this->total_amount ?? $this->grand_amount ?? '0.00'),
                'price_currency' => $this->currency ?: 'USD',
                // Ensure pay_amount is valid decimal string (not empty)
                'pay_amount' => (string) ($this->total_amount ?? $this->grand_amount ?? '0.00'),
                'pay_currency' => $this->currency ?: 'USD',
                'payment_status' => $this->payment_status ?: 'pending',
                'order_description' => 'Order ' . ($this->order_number ?: $this->id),
                'invoice_url' => $this->payment_invoice_url ?: '',
            ];
            $data = array_filter(array_merge($base, $overrides), function($v){ return $v !== null; });

            if ($invoice) {
                $invoice->fill($data);
                if ($invoice->isDirty()) { $invoice->save(); }
                return $invoice;
            }

            return \App\Models\Invoice::create($data);
        } catch (\Throwable $e) {
            \Log::error('ensureInvoice failed', ['order_id'=>$this->id, 'error'=>$e->getMessage()]);
            return null;
        }
    }

    // --- Tax-free policy: always enforce zero tax ---
    /**
     * Accessor to always expose tax_amount as 0.00
     */
    public function getTaxAmountAttribute($value): string
    {
        return number_format(0, 2, '.', '');
    }

    /**
     * Mutator to force tax_amount to 0 on write
     */
    public function setTaxAmountAttribute($value): void
    {
        $this->attributes['tax_amount'] = 0;
    }


    // Enhanced relationships for XUI integration

    public function serverClients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServerClient::class, 'order_server_clients')
                    ->withPivot(['provision_status', 'provision_error', 'provision_attempts'])
                    ->withTimestamps();
    }

    /**
     * Legacy alias for tests calling $order->clients() expecting BelongsToMany collection via pivot.
     */
    public function clients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServerClient::class, 'order_server_clients', 'order_id', 'server_client_id')
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
        // Use orderServerClients (pivot-backed records) for accurate counts
        $provisions = $this->orderServerClients()->get();

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
