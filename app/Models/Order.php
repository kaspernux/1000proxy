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
}
