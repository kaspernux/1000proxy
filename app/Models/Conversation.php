<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type', 'name', 'description', 'privacy', 'allow_attachments', 'allow_reactions',
    'created_by_id', 'created_by_type', 'archived_at',
    ];

    protected $casts = [
        'allow_attachments' => 'boolean',
        'allow_reactions' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function creator(): MorphTo { return $this->morphTo(__FUNCTION__, 'created_by_type', 'created_by_id'); }
    public function participants(): HasMany { return $this->hasMany(ConversationParticipant::class); }
    public function messages(): HasMany { return $this->hasMany(Message::class); }

    public function scopeForParticipant($query, $participant): void
    {
        $query->whereHas('participants', function ($q) use ($participant) {
            $q->where('participant_type', get_class($participant))
                ->where('participant_id', $participant->getKey());
        });
    }

    public function canJoin($actor): bool
    {
        // privacy rules: public anyone with link; private only invited; internal only Users
        if ($this->privacy === 'public') return true;
        if ($this->privacy === 'internal' && $actor instanceof User) return true;
        // private: must be participant already or explicitly invited (handled elsewhere)
        return $this->participants()->where('participant_type', get_class($actor))->where('participant_id', $actor->getKey())->exists();
    }
}
