<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;

class ActivityLog extends Model
{
    use HasFactory, Prunable;
    protected $fillable = [
        'user_id',
        'customer_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Polymorphic relation to the subject model.
     */
    public function subject()
    {
        return $this->morphTo(__FUNCTION__, 'subject_type', 'subject_id');
    }

    /**
     * Human-friendly subject display: ModelName#id or key attribute when available.
     */
    public function getSubjectDisplayAttribute(): string
    {
        $type = class_basename($this->subject_type ?? '');
        $id = $this->subject_id ?? '—';
        $subject = $this->relationLoaded('subject') ? $this->subject : null;
        $label = null;

        if ($subject) {
            foreach (['name', 'title', 'email', 'id'] as $attr) {
                if (isset($subject->{$attr})) {
                    $label = (string) $subject->{$attr};
                    break;
                }
            }
        }

        return $label ? "$type: $label" : ($type ? "$type #$id" : (string) $id);
    }

    // Scopes
    public function scopeForSubject($query, string $type, int|string $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    /**
     * Prune logs older than 90 days by default.
     */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(90));
    }
}
