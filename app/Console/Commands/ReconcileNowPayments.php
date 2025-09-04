<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;

class ReconcileNowPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nowpayments:reconcile {--limit=50 : Max transactions to process per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile pending NowPayments wallet top-ups by querying NowPayments API and updating local transactions';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $this->info("Starting NowPayments reconciliation (limit={$limit})...");

        $txs = WalletTransaction::where('type', 'deposit')
            ->where('status', 'pending')
            ->whereNotNull('payment_id')
            ->latest()
            ->take($limit)
            ->get();

        if ($txs->isEmpty()) {
            $this->info('No pending transactions with payment_id found.');
            return 0;
        }

        $nowService = app(\App\Services\PaymentGateways\NowPaymentsService::class);
        $processed = 0;
        foreach ($txs as $tx) {
            try {
                $this->line("Checking payment_id={$tx->payment_id} (tx_id={$tx->id})...");
                $res = $nowService->verifyPayment($tx->payment_id);
                if (!empty($res['success']) && !empty($res['data'])) {
                    $status = strtolower($res['data']['status'] ?? ($res['data']['payment_status'] ?? ''));
                    if (in_array($status, ['finished', 'confirmed', 'paid', 'completed'])) {
                        $tx->update(['status' => 'completed']);
                        $wallet = $tx->wallet;
                        if ($wallet) {
                            $wallet->increment('balance', $tx->amount);
                        }
                        $this->info("Marked tx={$tx->id} as completed (status={$status})");
                        $processed++;
                    } else {
                        $this->line("Payment {$tx->payment_id} status={$status}; skipping.");
                    }
                } else {
                    $this->warn("Failed to verify payment {$tx->payment_id}: " . ($res['error'] ?? 'unknown'));
                }
            } catch (\Exception $e) {
                $this->error("Error reconciling tx={$tx->id}: " . $e->getMessage());
                Log::warning('ReconcileNowPayments error', ['tx' => $tx->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Reconciliation finished. Processed={$processed}, Scanned={$txs->count()}.");
        return 0;
    }
}
