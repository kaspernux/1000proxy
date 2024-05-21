<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerReview extends Model
    {
    use HasFactory;
    protected $table = 'server_reviews';

    protected $fillable = [
        'server_id',
        'customer_id',
        'comments',
    ];

    public function server(): BelongsTo
        {
        return $this->belongsTo(Server::class);
        }

    public function customer(): BelongsTo
        {
        return $this->belongsTo(Customer::class);
        }
    public function approve()
        {
        $this->approved = true;
        $this->save();
        }
    }
