<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['message_id', 'disk', 'path', 'filename', 'mime_type', 'size', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function message(): BelongsTo { return $this->belongsTo(Message::class); }
}
