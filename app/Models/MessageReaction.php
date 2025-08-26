<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageReaction extends Model
{
    use HasFactory;

    protected $fillable = ['message_id', 'reactor_id', 'reactor_type', 'emoji'];

    public function message(): BelongsTo { return $this->belongsTo(Message::class); }
    public function reactor(): MorphTo { return $this->morphTo(); }
}
