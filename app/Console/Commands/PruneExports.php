<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\PruneOldExportsJob;

class PruneExports extends Command
{
    protected $signature = 'exports:prune {--days= : Override retention days}';
    protected $description = 'Prune old export and analytics artifact files';

    public function handle(): int
    {
        $days = $this->option('days');
        dispatch(new PruneOldExportsJob($days ? (int)$days : null));
        $this->info('Prune job dispatched' . ($days ? " (override days=$days)" : '')); 
        return self::SUCCESS;
    }
}
