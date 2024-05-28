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
        'brand_id',
        'server_category_id',
        'server_id',
        'sever_inbound_id',
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


    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function serverInbounds()
    {
        return $this->hasMany(ServerInbound::class);
    }

    public function serverCategory()
    {
        return $this->belongsTo(ServerCategory::class, 'category_id');
    }

     public function brands(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems(): BelongsTo
    {
        return $this->hasMany(OrderItem::class);
    }


}