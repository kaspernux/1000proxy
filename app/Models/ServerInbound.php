<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerInbound extends Model
{
    use HasFactory;

    protected $table = 'server_inbounds';

    protected $fillable = [
        'server_id',
        'protocol',
        'port',
        'enable',
        'remark',
        'expiryTime',
        'settings',
        'streamSettings',
        'sniffing',
        'up',
        'down',
        'total'
    ];

    protected $casts = [
        'up' => 'integer',
        'down' => 'integer',
        'total' => 'integer',
        'enable' => 'boolean',
        'expiryTime' => 'datetime',
        'clientStats' => 'array',
        'settings' => 'array',
        'streamSettings' => 'array',
        'sniffing' => 'array',
    ];

    

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class, 'server_inbound_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }
}

