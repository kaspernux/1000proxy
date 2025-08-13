<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',          // machine key, e.g., 'order_ready'
        'name',         // human-readable name
        'channel',      // telegram|email|sms|system
        'locale',       // en, ru, ...
        'subject',      // optional for email
        'body',         // template body (Telegram-safe HTML or Markdown)
        'enabled',      // bool
        'notes',        // optional admin notes
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function byKey(string $key, ?string $locale = null, ?string $channel = null): ?self
    {
        $q = static::query()->where('key', $key);
        if ($locale) { $q->where('locale', $locale); }
        if ($channel) { $q->where('channel', $channel); }
        return $q->orderByDesc('enabled')->orderByDesc('id')->first();
    }
}
