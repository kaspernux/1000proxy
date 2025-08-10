<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ClientProvisioningService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProcessXuiOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Order $order;
    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(ClientProvisioningService $provisioningService): void
    {
        Log::info("🚀 Starting enhanced XUI processing for Order #{$this->order->id}");

        try {
            // Safety guard: only proceed if order payment_status is 'paid'
            $this->order->refresh();
            if ($this->order->payment_status !== 'paid') {
                Log::warning('⏸ Skipping XUI provisioning for unpaid order', [
                    'order_id' => $this->order->id,
                    'payment_status' => $this->order->payment_status,
                ]);
                return; // Exit without error so job won't retry prematurely
            }
            // Use the enhanced provisioning service
            $results = $provisioningService->provisionOrder($this->order);

            Log::info("✅ Enhanced XUI processing completed for Order #{$this->order->id}", [
                'results_summary' => $this->summarizeResults($results),
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Enhanced XUI processing failed for Order #{$this->order->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark order as disputed if all retries fail
            if ($this->attempts() >= $this->tries) {
                $this->order->updateStatus('dispute');
                Log::error("🔥 Order #{$this->order->id} marked as disputed after {$this->tries} failed attempts");
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("🔥 ProcessXuiOrder permanently failed", [
            'order_id' => $this->order->id,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark order as disputed
        $this->order->updateStatus('dispute');
    }

    /**
     * Summarize provisioning results for logging
     */
    protected function summarizeResults(array $results): array
    {
        $summary = [
            'total_items' => count($results),
            'total_requested' => 0,
            'total_provisioned' => 0,
            'success_rate' => 0,
        ];

        foreach ($results as $result) {
            $summary['total_requested'] += $result['quantity_requested'] ?? 0;
            $summary['total_provisioned'] += $result['quantity_provisioned'] ?? 0;
        }

        if ($summary['total_requested'] > 0) {
            $summary['success_rate'] = round(
                ($summary['total_provisioned'] / $summary['total_requested']) * 100,
                2
            );
        }

        return $summary;
    }

    // At the bottom of the ProcessXuiOrder class (above the last closing bracket)
    public static function dispatchWithDependencies(Order $order): void
    {
        // Eager load items + nested serverPlan to avoid nulls in the job
        $order->loadMissing('items.serverPlan');

        // Dispatch job
        dispatch(new self($order));
    }
}