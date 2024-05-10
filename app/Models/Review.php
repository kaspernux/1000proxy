<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
    {
    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'server_id',
        'rating',
        'review',
        'approved',
    ];

    public function user()
        {
        return $this->belongsTo(User::class);
        }

    public function serverReview()
        {
        return $this->belongsTo(ServerReview::class);
        }

    public function approve()
        {
        $this->approved = true;
        $this->save();
        }

    }