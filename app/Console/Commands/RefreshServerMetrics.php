<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Server;
use App\Services\ServerManagementService;

class RefreshServerMetrics extends Command
{
    protected $signature = 'metrics:refresh {--force : Force refresh by bypassing caches}';
    protected $description = 'Refresh and warm dashboard metrics and per-server live caches';

    public function handle(ServerManagementService $svc): int
    {
        $force = (bool) $this->option('force');

        if ($force) {
            $this->info('Forcing full metrics refresh...');
            $svc->forceRefreshDashboardCaches();
            $this->info('Dashboard caches cleared and warmed.');
        } else {
            $this->info('Warming per-server metrics (cache-respecting)...');
            $servers = Server::where('is_active', true)->get();
            foreach ($servers as $server) {
                try {
                    $svc->monitorServerPerformance($server, false);
                    $this->line("- Warmed metrics for server #{$server->id} {$server->name}");
                } catch (\Throwable $e) {
                    $this->warn("! Failed metrics for server #{$server->id}: {$e->getMessage()}");
                }
            }
            $this->info('Aggregate metrics will refresh on next dashboard load or via cache TTLs.');
        }

        return 0;
    }
}
