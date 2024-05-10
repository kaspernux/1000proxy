<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
    {
    protected $table = 'ratings';
    protected $fillable = [
        'user_id',
        'server_id',
        'rating',
    ];

    public function user()
        {
        return $this->belongsTo(User::class);
        }

    public function serverRating(): BelongsTo
        {
        return $this->belongsTo(ServerRating::class);
        }

    public static function calculateAverageRating($rateId)
        {
        return self::where('server_rating_id', $rateId)
            ->avg('rating');
        }
    }