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
        'server_category_id',
        'name',
        'price',
        'type',
        'days',
        'volume',
    ];

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(ServerInbound::class, 'server_inbound_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(ServerClient::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServerCategory::class, 'server_category_id');
    }
}
