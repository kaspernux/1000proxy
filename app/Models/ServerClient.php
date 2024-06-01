<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerClient extends Model
{
    use HasFactory;

    protected $table = 'server_clients';

    protected $fillable = [
        'customer_id',
        'server_inbound_id',
        'name',
        'slug',
        'description',
        'price',
        'client_id',
        'alter_id',
        'email',
        'limit_ip',
        'total_gb',
        'expiry_time',
        'tg_id',
        'sub_id',
        'qr_code_sub',
        'qr_code_sub_json',
        'qr_code_client',
        'enabled',
        'is_featured',
        'in_stock',
        'capacity',
        'reset',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function serverInbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }
}
