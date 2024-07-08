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
        'userId',
        'up',
        'down',
        'total',
        'remark',
        'enable',
        'expiryTime',
        'clientStats',
        'listen',
        'port',
        'protocol',
        'settings',
        'streamSettings',
        'tag',
        'sniffing',
    ];

    protected $casts = [
        'clientStats' => 'array',
        'settings' => 'array',
        'streamSettings' => 'array',
        'sniffing' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class, 'server_inbound_id');
    }

    public function traffics(): HasMany
    {
        return $this->hasMany(ClientTraffic::class);
    }
}
