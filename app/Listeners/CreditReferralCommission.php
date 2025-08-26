<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Customer;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;

class CreditReferralCommission
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order->fresh(['customer']);
        if (! $order || ! $order->customer) {
            return;
        }

        $referred = $order->customer;

        // No referrer attached, nothing to do
        if (empty($referred->refered_by)) {
            return;
        }

        $referrer = Customer::find($referred->refered_by);
        if (! $referrer) {
            return;
        }

        // Prevent self-referral and zero/negative orders
        $orderTotal = (float) ($order->total_amount ?? $order->grand_amount ?? 0);
        if ($referrer->id === $referred->id || $orderTotal <= 0) {
            return;
        }

        // Idempotency: skip if already credited for this order
        $alreadyCredited = WalletTransaction::query()
            ->where('customer_id', $referrer->id)
            ->where('type', 'credit')
            ->where('metadata->referral', true)
            ->where('metadata->order_id', $order->id)
            ->exists();

        if ($alreadyCredited) {
            return;
        }

        // Determine commission rate (1%..3%) based on referrer level (by successful referrals count)
        $rate = $this->determineRate($referrer);
        if ($rate <= 0) {
            return;
        }

        $commission = round($orderTotal * ($rate / 100), 2);
        if ($commission <= 0) {
            return;
        }

        try {
            $wallet = $referrer->getWallet();
            // Increase balance and create a credit transaction with metadata for traceability
            $wallet->increment('balance', $commission);
            $wallet->transactions()->create([
                'wallet_id' => $wallet->id,
                'customer_id' => $referrer->id,
                'amount' => $commission,
                'type' => 'credit',
                'status' => 'completed',
                'reference' => 'RefComm_' . strtoupper(\Str::random(10)),
                'description' => sprintf(
                    'Referral commission from Order #%d (referred: Customer #%d) at %s%%',
                    $order->id,
                    $referred->id,
                    number_format($rate, 0)
                ),
                'metadata' => [
                    'referral' => true,
                    'referred_customer_id' => $referred->id,
                    'order_id' => $order->id,
                    'rate' => $rate,
                    'base_amount' => $orderTotal,
                ],
            ]);

            Log::info('✅ Referral commission credited', [
                'referrer_id' => $referrer->id,
                'referred_id' => $referred->id,
                'order_id' => $order->id,
                'rate' => $rate,
                'amount' => $commission,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed crediting referral commission', [
                'order_id' => $order->id,
                'referrer_id' => $referrer->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Simple tiering for referral rate (max 3%).
     * - < 5 active referrals => 1%
     * - 5..14 active referrals => 2%
     * - 15+ active referrals => 3%
     */
    protected function determineRate(Customer $referrer): float
    {
        // Active referrals = referred customers who have at least one paid order
        $activeReferrals = \App\Models\Order::query()
            ->whereIn('customer_id', function ($q) use ($referrer) {
                $q->select('id')->from('customers')->where('refered_by', $referrer->id);
            })
            ->where('payment_status', 'paid')
            ->distinct('customer_id')
            ->count('customer_id');

        if ($activeReferrals >= 15) { return 3.0; }
        if ($activeReferrals >= 5) { return 2.0; }
        return 1.0;
    }
}
