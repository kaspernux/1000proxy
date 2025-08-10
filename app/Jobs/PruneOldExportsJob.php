<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PruneOldExportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?int $days = null) {}

    public function handle(): void
    {
        $days = $this->days ?? config('exports.retention_days');
        $deleted = 0;
        foreach (['exports/orders', 'exports/analytics'] as $dir) {
            if (!Storage::disk('local')->exists($dir)) {
                continue;
            }
            foreach (Storage::disk('local')->files($dir) as $file) {
                $modified = Storage::disk('local')->lastModified($file);
                if ($modified < now()->subDays($days)->getTimestamp()) {
                    if (Storage::disk('local')->delete($file)) {
                        $deleted++;
                    }
                }
            }
        }
        if ($deleted > 0) {
            // Invalidate analytics caches after filesystem changes
            Cache::tags(['analytics'])->flush();
        }
        Log::info('PruneOldExportsJob completed', ['deleted' => $deleted, 'retention_days' => $days]);
    }
}
