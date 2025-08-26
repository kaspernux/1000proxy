<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMetric extends Model
{
    use HasFactory, \App\Traits\LogsActivity;

    protected $table = 'client_metrics';

    protected $fillable = [
        'server_client_id',
        'customer_id',
        'server_id',
        'is_online',
        'latency_ms',
        'total_bytes',
        'measured_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'latency_ms' => 'integer',
        'total_bytes' => 'integer',
        'measured_at' => 'datetime',
    ];

    public function serverClient(): BelongsTo
    {
        return $this->belongsTo(ServerClient::class, 'server_client_id', 'id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
