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
        'customer_id',
        'server_id',
        'server_plan_id',
        'server_inbound_id',
        'token',
        'payments_id',
        'fileid',
        'remark',
        'uuid',
        'protocol',
        'expire_date',
        'link',
        'amount',
        'status',
        'date',
        'notif',
        'rahgozar',
        'agent_bought',
    ];

    public function serverPlan(): BelongsTo
        {
        return $this->belongsTo(ServerPlan::class);
        }

    public function serverInbound(): BelongsTo
        {
        return $this->belongsTo(ServerInbound::class);
        }

    public function server(): BelongsTo
        {
        return $this->belongsTo(Server::class);
        }

    public function customer(): BelongsTo
        {
        return $this->belongsTo(Customer::class);
        }

    public function order(): BelongsTo
        {
        return $this->belongsTo(Order::class);
        }

    public function payments(): BelongsTo
        {
        return $this->belongsTo(Payments::class);
        }
    }