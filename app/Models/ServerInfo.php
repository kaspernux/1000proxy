<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerInfo extends Model
{
    use HasFactory;

    protected $table = 'server_infos';

    protected $fillable = [
        'server_id',
        'title',
        'ucount',
        'remark',
        'flag',
        'active',
        'state',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
