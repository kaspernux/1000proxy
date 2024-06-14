<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_plan_id',
        'server_client_id',
        'quantity',
        'unit_amount',
        'total_amount',
        'agent_bought',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function serverClient(): BelongsTo
    {
        return $this->belongsTo(ServerClient::class);
    }

    public function serverPlan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class);
    }
}
