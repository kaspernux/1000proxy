<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchFeatureAdXuiInfo;
use App\Models\FeatureAd;

class DispatchFeatureAdXuiFetch extends Command
{
    protected $signature = 'featuread:fetch-xui {--only-active}';
    protected $description = 'Dispatch X-UI fetch jobs for FeatureAds';

    public function handle(): int
    {
        $query = FeatureAd::query();
        if ($this->option('only-active')) {
            $query->where('is_active', true);
        }
        $ads = $query->get();
        foreach ($ads as $ad) {
            FetchFeatureAdXuiInfo::dispatch($ad->id);
            $this->info('Dispatched for FeatureAd ' . $ad->id);
        }
        return 0;
    }
}
