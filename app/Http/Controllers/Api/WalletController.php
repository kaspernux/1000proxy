<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    /**
     * Get wallet balance and info
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            // Create wallet if it doesn't exist
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'currency' => 'USD',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet->balance,
                'currency' => $wallet->currency,
                'created_at' => $wallet->created_at,
                'updated_at' => $wallet->updated_at,
            ]
        ]);
    }

    /**
     * Get wallet transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                ]
            ]);
        }

        $query = $wallet->transactions()->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ]
        ]);
    }

    /**
     * Get wallet statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_deposits' => 0,
                    'total_spent' => 0,
                    'total_transactions' => 0,
                    'current_balance' => 0,
                ]
            ]);
        }

        $stats = [
            'total_deposits' => $wallet->transactions()
                ->where('type', 'credit')
                ->where('description', 'not like', '%Refund%')
                ->sum('amount'),
            'total_spent' => $wallet->transactions()
                ->where('type', 'debit')
                ->sum('amount'),
            'total_refunds' => $wallet->transactions()
                ->where('type', 'credit')
                ->where('description', 'like', '%Refund%')
                ->sum('amount'),
            'total_transactions' => $wallet->transactions()->count(),
            'current_balance' => $wallet->balance,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Create deposit request
     */
    public function createDeposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'currency' => 'required|in:BTC,ETH,LTC,XMR,SOL,USDT',
        ]);

        $user = $request->user();
        $amount = $request->amount;
        $currency = $request->currency;

        try {
            // Create deposit transaction record
            $transaction = WalletTransaction::create([
                'wallet_id' => $user->wallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'description' => "Deposit via {$currency}",
                'reference' => 'deposit_' . time(),
            ]);

            // Here you would integrate with your payment provider
            // For now, we'll return a mock response
            $paymentUrl = config('app.url') . "/payment/deposit/{$transaction->id}";

            return response()->json([
                'success' => true,
                'message' => 'Deposit request created',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_url' => $paymentUrl,
                    'status' => 'pending',
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Deposit creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Deposit creation failed'
            ], 500);
        }
    }

    /**
     * Get deposit status
     */
    public function depositStatus(int $transactionId, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $transaction = WalletTransaction::where('wallet_id', $user->wallet->id)
            ->findOrFail($transactionId);

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ]
        ]);
    }

    /**
     * Get supported currencies for deposits
     */
    public function currencies(): JsonResponse
    {
        $currencies = [
            'BTC' => [
                'name' => 'Bitcoin',
                'symbol' => 'BTC',
                'min_amount' => 0.001,
                'max_amount' => 10,
                'fee_percentage' => 1.0,
            ],
            'ETH' => [
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'min_amount' => 0.01,
                'max_amount' => 100,
                'fee_percentage' => 1.0,
            ],
            'LTC' => [
                'name' => 'Litecoin',
                'symbol' => 'LTC',
                'min_amount' => 0.1,
                'max_amount' => 1000,
                'fee_percentage' => 0.5,
            ],
            'XMR' => [
                'name' => 'Monero',
                'symbol' => 'XMR',
                'min_amount' => 0.01,
                'max_amount' => 100,
                'fee_percentage' => 1.0,
            ],
            'SOL' => [
                'name' => 'Solana',
                'symbol' => 'SOL',
                'min_amount' => 0.1,
                'max_amount' => 1000,
                'fee_percentage' => 0.5,
            ],
            'USDT' => [
                'name' => 'Tether',
                'symbol' => 'USDT',
                'min_amount' => 1,
                'max_amount' => 10000,
                'fee_percentage' => 0.5,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $currencies
        ]);
    }

    /**
     * Get transaction by ID
     */
    public function transaction(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $transaction = WalletTransaction::where('wallet_id', $user->wallet->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }
}
