<?php

namespace App\Jobs;

use App\Services\ClientLifecycleService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClientLifecycleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $operation;
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(string $operation = 'all')
    {
        $this->operation = $operation;
    }

    public function handle(ClientLifecycleService $lifecycleService): void
    {
        Log::info("🔄 Starting client lifecycle job: {$this->operation}");

        try {
            $results = [];

            switch ($this->operation) {
                case 'expired':
                    $results['expired'] = $lifecycleService->processExpiredClients();
                    break;

                case 'expiring':
                    $results['expiring'] = $lifecycleService->processExpiringClients();
                    break;

                case 'traffic':
                    $results['traffic_sync'] = $lifecycleService->syncTrafficUsage();
                    $results['traffic_violations'] = $lifecycleService->processTrafficLimitViolations();
                    break;

                case 'all':
                default:
                    $results['expired'] = $lifecycleService->processExpiredClients();
                    $results['expiring'] = $lifecycleService->processExpiringClients();
                    $results['traffic_sync'] = $lifecycleService->syncTrafficUsage();
                    $results['traffic_violations'] = $lifecycleService->processTrafficLimitViolations();
                    break;
            }

            Log::info("✅ Client lifecycle job completed: {$this->operation}", [
                'results_summary' => $this->summarizeResults($results),
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Client lifecycle job failed: {$this->operation}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("🔥 ClientLifecycleJob permanently failed", [
            'operation' => $this->operation,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Summarize results for logging
     */
    protected function summarizeResults(array $results): array
    {
        $summary = [];

        foreach ($results as $operation => $result) {
            $summary[$operation] = [
                'processed' => $result['processed'] ?? 0,
                'errors' => count($result['errors'] ?? []),
            ];

            // Add specific metrics for each operation
            if ($operation === 'expired') {
                $summary[$operation]['suspended'] = $result['suspended'] ?? 0;
                $summary[$operation]['renewed'] = $result['renewed'] ?? 0;
                $summary[$operation]['terminated'] = $result['terminated'] ?? 0;
            } elseif ($operation === 'expiring') {
                $summary[$operation]['notifications_sent'] = $result['notifications_sent'] ?? 0;
                $summary[$operation]['auto_renewals_queued'] = $result['auto_renewals_queued'] ?? 0;
            } elseif ($operation === 'traffic_sync') {
                $summary[$operation]['servers_processed'] = $result['servers_processed'] ?? 0;
                $summary[$operation]['clients_updated'] = $result['clients_updated'] ?? 0;
            } elseif ($operation === 'traffic_violations') {
                $summary[$operation]['suspended'] = $result['suspended'] ?? 0;
                $summary[$operation]['notifications_sent'] = $result['notifications_sent'] ?? 0;
            }
        }

        return $summary;
    }
}
