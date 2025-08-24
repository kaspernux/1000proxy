<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public function log(string $action, ?object $subject = null, array $properties = []): void
    {
        try {
            $userId = auth('web')->id() ?? auth()->id();
            $customerId = auth('customer')->id();

            ActivityLog::create([
                'user_id' => $userId,
                'customer_id' => $customerId,
                'action' => $action,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->id,
                'properties' => $properties,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Swallow exceptions; logging must never break app flow.
        }
    }
}
