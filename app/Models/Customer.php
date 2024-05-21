<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Customer extends Model
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

    public function payments(): HasMany
        {
        return $this->hasMany(Payments::class);
        }

    public function serverReviews(): HasMany
        {
        return $this->hasMany(ServerReview::class);
        }

    public function serverRatings(): HasMany
        {
        return $this->hasMany(ServerRating::class);

        }

    public function orderItems(): HasMany
        {
        return $this->hasMany(OrderItem::class);

        }
    public function paymentMethods(): HasMany
        {
        return $this->hasMany(PaymentMethod::class);
        }

    // Define the relationship indicating the customer who referred this customer
    public function referrer()
    {
        return $this->belongsTo(Customer::class, 'refered_by');
    }

    // Define the relationship indicating customers referred by this customer
    public function referredCustomers()
    {
        return $this->hasMany(Customer::class, 'refered_by');
    }

}