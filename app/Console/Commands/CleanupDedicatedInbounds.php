<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServerInbound;
use App\Models\OrderServerClient;
use Illuminate\Support\Facades\Log;

class CleanupDedicatedInbounds extends Command
{
    protected $signature = 'inbounds:cleanup-dedicated {--dry-run : Show what would be deleted without deleting}';
    protected $description = 'Remove unused dedicated inbounds (no active clients) older than a grace period';

    public function handle(): int
    {
        $graceMinutes = config('provisioning.dedicated_inbound_cleanup_grace', 30);
        $dry = $this->option('dry-run');
        $cutoff = now()->subMinutes($graceMinutes);

        $query = ServerInbound::query()
            ->where('remark', 'LIKE', 'DEDICATED O%')
            // No active clients attached
            ->whereDoesntHave('clients', function($q){
                $q->whereNull('terminated_at')
                  ->where('status', 'active');
            })
            // Not referenced by any active (paid/processing) order via pivot
            ->whereDoesntHave('clients.orders', function($q){
                $q->whereIn('payment_status', ['paid','processing']);
            })
            ->where(function($q) use ($cutoff){
                $q->whereNull('updated_at')->orWhere('updated_at', '<', $cutoff);
            });

        $count = $query->count();
        if ($count === 0) {
            $this->info('No unused dedicated inbounds found.');
            return self::SUCCESS;
        }

        $this->info("Found {$count} unused dedicated inbound(s). Dry run: " . ($dry ? 'YES' : 'NO'));

        if ($dry) {
            $query->get()->each(function($inbound){
                $this->line("Would delete inbound #{$inbound->id} port {$inbound->port} remark {$inbound->remark}");
            });
            return self::SUCCESS;
        }

        $deleted = 0;
        $query->get()->each(function(ServerInbound $inbound) use (&$deleted){
            try {
                // Attempt remote deletion before local
                try {
                    $server = $inbound->server;
                    if ($server) {
                        $xui = new \App\Services\XUIService($server);
                        $xui->deleteInbound($inbound->remote_id);
                    }
                } catch (\Throwable $remoteEx) {
                    Log::warning('Remote delete failed for inbound during cleanup', [
                        'inbound_id' => $inbound->id,
                        'error' => $remoteEx->getMessage(),
                    ]);
                }
                $inbound->delete();
                $deleted++;
            } catch (\Throwable $e) {
                Log::error('Failed deleting dedicated inbound', [
                    'inbound_id' => $inbound->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        $this->info("Deleted {$deleted} dedicated inbound(s).");
        return self::SUCCESS;
    }
}
