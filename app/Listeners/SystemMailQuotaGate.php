<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Notifications\Events\NotificationSending;

class SystemMailQuotaGate
{
    public function handle(NotificationSending $event): bool
    {
        // Only throttle mail channel
        if (($event->channel ?? null) !== 'mail') {
            return true; // allow
        }

        $notification = $event->notification;
        $config = config('mail_quota');

        $isSystem = $notification instanceof \App\Contracts\SystemMail
            || in_array(get_class($notification), (array)($config['system_notifications'] ?? []), true);

        if (!$isSystem) {
            return true; // not a system email
        }

        $dateKey = now()->format('Y-m-d');
        $key = ($config['cache_key'] ?? 'system_mail_quota') . ':' . $dateKey;
        $limit = (int) ($config['daily_limit'] ?? 50);

        // Initialize counter for today if missing
        $count = (int) Cache::get($key, 0);
        if ($count >= $limit) {
            // Quota exceeded; skip sending
            logger()->channel('security')->warning('System email suppressed due to daily quota', [
                'notification' => get_class($notification),
                'limit' => $limit,
                'count' => $count,
            ]);
            return false;
        }

        // Reserve one slot optimistically (expires at end of day)
        $ttl = now()->endOfDay()->diffInSeconds(now());
        Cache::put($key, $count + 1, $ttl);
        return true;
    }
}
