<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Server;
use App\Models\DownloadableItem;
use App\Models\ServerPlan;
use App\Models\ServerCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerInfo extends Model
{
    use HasFactory;

    protected $table = 'server_infos';

    protected $fillable = [
        'server_id',
        'title',
        'ucount',
        'remark',
        'tag',
        'active',
        'state',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function downloadableItems(): HasMany
    {
        // maps downloadable_items.server_id → this->server_id
        return $this->hasMany(DownloadableItem::class, 'server_id', 'server_id');
    }
}
