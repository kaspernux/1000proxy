<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerClient extends Model
    {
    protected $fillable = [
        'id',
        'server_inbound_id',
        'enable',
        'email',
        'up',
        'down',
        'expiry_time',
        'total',
        'reset',
    ];

    protected $casts = [
        'enable' => 'boolean', // Cast 'enable' to boolean
        'expiry_time' => 'datetime', // Cast 'expiryTime' to datetime
    ];

    // Define the relationship with the Inbound model
    public function serverInbound(): BelongsTo
        {
        return $this->belongsTo(ServerInbound::class);
        }

    }