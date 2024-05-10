<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
    {
    protected $table = 'inventory_logs';

    protected $fillable = [
        'server_plan_id',
        'quantity_change',
        'reason',
    ];

    public function serverPlan(): BelongsTo
        {
        return $this->belongsTo(ServerPlan::class);
        }
    }