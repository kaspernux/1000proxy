<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';


    protected $fillable = [
        'order_id',
        'server_plan_id',
        'quantity',
        'unit_amount',
        'total_amount',
        'agent_bought',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function serverPlan(): BelongsTo
    {
        return $this->belongsTo(ServerPlan::class);
    }

    public function getQrCodes(): array
{
    $client = \App\Models\ServerClient::query()
        ->where('plan_id', $this->server_plan_id)
        ->where('email', 'LIKE', '%#ID ' . auth('customer')->id())
        ->first();

    return [
        'clientQr' => $client?->qr_code_client,
        'subQr' => $client?->qr_code_sub,
        'jsonQr' => $client?->qr_code_sub_json,
    ];
}

}