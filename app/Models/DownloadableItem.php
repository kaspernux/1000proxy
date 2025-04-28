<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ServerInfo;
use App\Models\Server;


class DownloadableItem extends Model
{
    protected $table = 'downloadable_items';

    protected $fillable = [
        'server_id',         // ← was server_client_id
        'file_url',
        'download_limit',
        'expiration_time',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }
}

