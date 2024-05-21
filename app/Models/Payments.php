<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;

    // Define the fillable fields
    protected $fillable = [
        'order_id',
        'customer_id',
        'hash_id',
        'description',
        'payment_method_id',
        'type',
        'server_plan_id',
        'volume',
        'day',
        'price',
        'tron_price',
        'request_date',
        'state',
        'agent_bought',
        'agent_count',
    ];

    // Define the relationship with the Customer model
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');

   }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function serverPlan()
    {
        return $this->belongsTo(ServerPlan::class, 'server_plan_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}