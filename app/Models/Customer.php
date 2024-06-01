<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'is_active',
        'image',
        'name',
        'email',
        'password',
        'telegram_id',
        'refcode',
        'wallet',
        'date',
        'phone',
        'refered_by',
        'step',
        'freetrial',
        'first_start',
        'temp',
        'is_agent',
        'discount_percent',
        'agent_date',
        'spam_info'
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ServerReview::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ServerRating::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    // Define the relationship indicating the customer who referred this customer
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'refered_by');
    }

    // Define the relationship indicating customers referred by this customer
    public function referredCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'refered_by');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class, 'refered_by');
    }

    public function traffics(): HasMany
    {
        return $this->hasMany(ClientTraffic::class);
    }
}