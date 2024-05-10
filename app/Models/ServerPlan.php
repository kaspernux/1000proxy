<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerPlan extends Model
    {
    use HasFactory;
    protected $table = 'server_plans';

    protected $fillable = [
        'server_id',
        'inbound_id',
        'category_id',
        'fileid',
        'acount',
        'limitip',
        'title',
        'protocol',
        'days',
        'volume',
        'type',
        'price',
        'descr',
        'pic',
        'active',
        'step',
        'date',
        'rahgozar',
        'dest',
        'serverNames',
        'spiderX',
        'flow',
        'custom_path',
        'custom_port',
        'custom_sni',
    ];

    protected $casts = [
        'active' => 'boolean', // Cast 'active' to boolean
    ];

    public function servers(): BelongsTo
        {
        return $this->belongsTo(Server::class);
        }

    public function serverCategories(): BelongsTo
        {
        return $this->belongsTo(ServerCategory::class);
        }

    public function serverInbounds(): HasMany
        {
        return $this->hasMany(ServerInbound::class);
        }
    }