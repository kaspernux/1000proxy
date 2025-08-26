<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'sender_id', 'sender_type', 'body', 'meta', 'is_edited', 'edited_at', 'is_deleted', 'delivered_at'
    ];

    protected $casts = [
        'meta' => 'array',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'edited_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function sender(): MorphTo { return $this->morphTo(); }
    public function attachments(): HasMany { return $this->hasMany(MessageAttachment::class); }
    public function reactions(): HasMany { return $this->hasMany(MessageReaction::class); }
    public function reads(): HasMany { return $this->hasMany(MessageRead::class); }
}
