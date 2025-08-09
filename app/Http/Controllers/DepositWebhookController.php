<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WalletTransaction;
use App\Models\Wallet;
use App\Services\PriceService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\PaymentController;

class DepositWebhookController extends Controller
{
    public function handleBtc(Request $request)
    {
        return $this->confirmDeposit($request, 'btc');
    }

    public function handleXmr(Request $request)
    {
        return $this->confirmDeposit($request, 'xmr');
    }

    public function handleSol(Request $request)
    {
        return $this->confirmDeposit($request, 'sol');
    }

    protected function confirmDeposit(Request $request, string $currency)
    {
        $address = $request->input('address');
        $amount = $request->input('amount');
        if (!$address || !$amount) {
            return response()->json(['error' => 'Invalid data'], 422);
        }
        $transaction = WalletTransaction::where('address', $address)
            ->where('currency', $currency)
            ->where('status', 'pending')
            ->first();
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found or already confirmed'], 404);
        }
        if ($transaction->status === 'confirmed') {
            return response()->json(['success' => false, 'message' => 'Already confirmed']);
        }
        // Use PaymentController logic for wallet crediting
        $paymentController = app(PaymentController::class);
        $result = $paymentController->confirmWalletDeposit($transaction, $amount);
        return response()->json($result);
    }
}
