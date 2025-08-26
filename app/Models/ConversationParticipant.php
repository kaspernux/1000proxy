<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'participant_id', 'participant_type', 'role', 'can_post', 'can_edit', 'can_delete', 'joined_at'
    ];

    protected $casts = [
        'can_post' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'joined_at' => 'datetime',
    ];

    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function participant(): MorphTo { return $this->morphTo(); }
}
