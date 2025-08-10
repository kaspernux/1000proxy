<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;
use App\Models\Customer;

class AutoSelector
{
    /**
     * Determine payment method + crypto default.
     * Preference: wallet (if active & sufficient) -> crypto (NowPayments) -> first active.
     * Returns array [method, cryptoCurrency].
     */
    public static function determine(array $activeSlugs, float $walletBalance, float $total): array
    {
        $keys = [];
        if (in_array('wallet', $activeSlugs, true)) $keys[] = 'wallet';
        if (in_array('nowpayments', $activeSlugs, true)) $keys[] = 'crypto';
        if (in_array('stripe', $activeSlugs, true)) $keys[] = 'stripe';
        if (in_array('mir', $activeSlugs, true)) $keys[] = 'mir';

        if (empty($keys)) return [null, null];

        if (in_array('wallet', $keys, true) && $walletBalance >= $total) {
            return ['wallet', null];
        }
        if (in_array('crypto', $keys, true)) {
            return ['crypto', 'xmr'];
        }
        $first = $keys[0];
        return [$first, $first === 'crypto' ? 'xmr' : null];
    }
}
