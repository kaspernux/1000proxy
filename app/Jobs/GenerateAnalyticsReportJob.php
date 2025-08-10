<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\BusinessIntelligenceService;
use App\Models\User;

class GenerateAnalyticsReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?int $userId = null, public array $filters = []) {}

    public function handle(BusinessIntelligenceService $bi): void
    {
        $data = $bi->getDashboardAnalytics($this->filters);
        // Build deterministic hash for this hour + filter set to avoid duplicates
        ksort($this->filters);
        $hashSource = json_encode([
            'hour' => now()->format('Y-m-d-H'),
            'filters' => $this->filters,
        ]);
        $hash = substr(sha1($hashSource), 0, 16);
        $baseDir = 'exports/analytics';
        $filename = $baseDir . '/analytics_' . now()->format('Ymd_H') . '_' . $hash . '.json';
        if (!Storage::disk('local')->exists($filename)) {
            Storage::disk('local')->put($filename, json_encode($data, JSON_PRETTY_PRINT));
        }

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new \App\Notifications\ExportReadyNotification($filename));
            }
        }
    }
}
