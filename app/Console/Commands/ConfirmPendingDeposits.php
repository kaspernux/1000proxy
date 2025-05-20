<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WalletTransaction;
use App\Services\PriceService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmPendingDeposits extends Command
{
    protected $signature = 'wallet:confirm-pending';
    protected $description = 'Check and confirm pending wallet deposits';

    public function handle()
    {
        $pending = WalletTransaction::where('status', 'pending')->get();

        if ($pending->isEmpty()) {
            $this->info('No pending transactions.');
            return;
        }

        foreach ($pending as $tx) {
            if ($tx->confirmed_at) {
                $this->warn("Already confirmed: {$tx->id}");
                continue;
            }

            $confirmed = $this->checkBlockchainConfirmation($tx->currency, $tx->address);

            if (!$confirmed['confirmed']) {
                continue;
            }

            if ($confirmed['amount'] < $tx->amount) {
                $this->warn("💸 Confirmed less than expected: {$tx->id} (Expected {$tx->amount}, got {$confirmed['amount']})");
                continue;
            }

            $price = app(PriceService::class)->getUsdPrice($tx->currency);
            $usdAmount = $confirmed['amount'] * $price;

            DB::transaction(function () use ($tx, $usdAmount, $confirmed) {
                $tx->wallet->increment('balance', $usdAmount);

                $tx->update([
                    'status' => 'confirmed',
                    'amount' => $confirmed['amount'],
                    'confirmed_at' => now(),
                ]);
            });

            Log::info("🔐 Confirmed {$tx->currency} deposit via cron", [
                'transaction_id' => $tx->id,
                'address' => $tx->address,
                'wallet_id' => $tx->wallet_id,
                'confirmed_amount' => $confirmed['amount'],
                'usd_value' => $usdAmount,
            ]);

            $this->info("✅ Confirmed {$tx->currency} deposit: {$tx->address}");
        }
    }

    protected function checkBlockchainConfirmation(string $currency, string $address): array
    {
        return match (strtolower($currency)) {
            'btc' => $this->checkBitcoin($address),
            'xmr' => $this->checkMonero($address),
            'sol' => $this->checkSolana($address),
            default => ['confirmed' => false],
        };
    }

    protected function checkBitcoin(string $address): array
    {
        $response = Http::get("https://api.blockcypher.com/v1/btc/main/addrs/{$address}/full");

        if ($response->failed()) {
            return ['confirmed' => false];
        }

        $totalReceived = ($response->json()['total_received'] ?? 0) / 100000000;

        return [
            'confirmed' => $totalReceived > 0,
            'amount' => $totalReceived,
        ];
    }

    protected function checkMonero(string $address): array
    {
        $transaction = WalletTransaction::where('address', $address)
            ->where('currency', 'xmr')
            ->where('status', 'pending')
            ->first();

        if (!$transaction || !$transaction->payment_id) {
            return ['confirmed' => false];
        }

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post('http://localhost:18082/json_rpc', [
                'jsonrpc' => '2.0',
                'id' => 'laravel',
                'method' => 'get_transfers',
                'params' => [
                    'in' => true,
                    'account_index' => 0,
                    'subaddr_indices' => [],
                ]
            ]);

        if ($response->failed()) {
            Log::warning('Monero RPC failed', ['address' => $address]);
            return ['confirmed' => false];
        }

        $transfers = $response->json()['result']['in'] ?? [];

        foreach ($transfers as $tx) {
            if (
                isset($tx['payment_id']) &&
                $tx['payment_id'] === $transaction->payment_id &&
                $tx['confirmations'] >= 10
            ) {
                return [
                    'confirmed' => true,
                    'amount' => (float) $tx['amount'] / 1e12,
                ];
            }
        }

        return ['confirmed' => false];
    }


    protected function checkSolana(string $address): array
    {
        $response = Http::post('https://api.mainnet-beta.solana.com', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getBalance',
            'params' => [$address],
        ]);

        if ($response->failed()) {
            return ['confirmed' => false];
        }

        $lamports = $response->json()['result']['value'] ?? 0;
        $solAmount = $lamports / 1e9;

        return [
            'confirmed' => $solAmount > 0,
            'amount' => $solAmount,
        ];
    }
    

}
