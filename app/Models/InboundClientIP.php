<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundClientIP extends Model
{
    use HasFactory;

    protected $table = 'inbound_client_ips';

    protected $fillable = [
        'client_email',
        'ips',
    ];
}