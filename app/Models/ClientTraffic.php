<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientTraffic extends Model
{
    use HasFactory;

    protected $table = 'client_traffics';

    protected $fillable = [
        'server_inbound_id',
        'customer_id',
        'enable',
        'email',
        'up',
        'down',
        'expiry_time',
        'total',
        'reset',
    ];

    public function serverInbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}