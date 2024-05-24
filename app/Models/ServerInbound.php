<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerInbound extends Model
    {
    use HasFactory;

    protected $fillable = [
        'user_id',
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
        'enable' => 'boolean',
        'expiryTime' => 'datetime',
        'clientStats' => 'array',
        'settings' => 'array',
        'streamSettings' => 'array',
        'sniffing' => 'array',
    ];

    public function serverClients(): HasMany
        {
        return $this->hasMany(ServerClient::class);
        }

    public function serverCategories(): HasMany
        {
        return $this->hasMany(ServerCategory::class);
        }

    public function serverPlan(): BelongsTo
        {
        return $this->belongsTo(ServerPlan::class);
        }

    public function server(): BelongsTo
        {
        return $this->belongsTo(Server::class);
        }

    public function orderItem(): BelongsTo
        {
        return $this->belongsTo(OrderItem::class);
        }
    }