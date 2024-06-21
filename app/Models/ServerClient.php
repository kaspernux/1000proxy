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
        'description',
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

    public function serverPlan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class);
    }
}