<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Customer extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $guard = 'customer'; // Ensure the correct guard is set

    protected $table = 'customers';

    protected $fillable = [
        'is_active',
        'image',
        'name',
        'email',
        'password',
        'tgId',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
        {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
        }


    // Define relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(ServerReview::class);
    }

    public function ratings()
    {
        return $this->hasMany(ServerRating::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function referrer()
    {
        return $this->belongsTo(Customer::class, 'refered_by');
    }

    public function referredCustomers()
    {
        return $this->hasMany(Customer::class, 'refered_by');
    }

    public function clients()
    {
        return $this->hasMany(ServerClient::class, 'refered_by');
    }

    public function traffics()
    {
        return $this->hasMany(ClientTraffic::class);
    }

}