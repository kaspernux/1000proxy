<?php

namespace App\Jobs;

use App\Models\FeatureAd;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchFeatureAdXuiInfo implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $featureAdId;

    public function __construct(int $featureAdId)
    {
        $this->featureAdId = $featureAdId;
    }

    public function handle(): void
    {
        $ad = FeatureAd::find($this->featureAdId);
        if (!$ad || !$ad->server) {
            return;
        }

        try {
            $xui = new \App\Services\XUIService($ad->server);
            $health = method_exists($xui, 'getHealthStatus') ? $xui->getHealthStatus() : null;
            $onlines = method_exists($xui, 'getOnlineClients') ? $xui->getOnlineClients() : null;

            $ad->metadata = array_merge($ad->metadata ?? [], [
                'xui_health' => $health,
                'xui_online_emails' => $onlines,
                'xui_total_online' => is_array($onlines) ? count($onlines) : 0,
                'last_fetched_at' => now()->toISOString(),
            ]);
            $ad->save();
        } catch (\Throwable $e) {
            logger()->channel('xui')->error('FetchFeatureAdXuiInfo failed: ' . $e->getMessage(), ['ad' => $this->featureAdId]);
        }
    }
}
