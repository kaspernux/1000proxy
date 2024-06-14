<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftList extends Model
{
    protected $table = 'gift_lists';

    protected $fillable = [
        'server_id',
        'volume',
        'day',
        'offset',
        'server_offset',
    ];

    protected $casts = [
        'day' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
