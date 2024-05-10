<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerRating extends Model
    {
    use HasFactory;
    protected $table = 'server_ratings';

    protected $fillable = [
        'server_id',
        'customer_id',
        'rating',
    ];

    public function server(): BelongsTo
        {
        return $this->belongsTo(Server::class);
        }

    public function customer(): BelongsTo
        {
        return $this->belongsTo(Customer::class);
        }
    }