<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        foreach (['created' => 'created', 'updated' => 'updated', 'deleted' => 'deleted'] as $event => $action) {
            static::$event(function ($model) use ($action) {
                try {
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => $action,
                        'subject_type' => get_class($model),
                        'subject_id' => $model->getKey(),
                        'properties' => [
                            'attributes' => $model->getAttributes(),
                            'changes' => method_exists($model, 'getChanges') ? $model->getChanges() : null,
                        ],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                } catch (\Throwable $e) {
                    // Fail silently to avoid breaking primary flow
                }
            });
        }
    }
}
