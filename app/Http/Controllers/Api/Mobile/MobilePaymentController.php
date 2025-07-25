<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\MobileAppDevelopmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile Payment Controller
 *
 * Handles mobile payment processing and wallet management
 */
class MobilePaymentController extends Controller
{
    protected $mobileService;

    public function __construct(MobileAppDevelopmentService $mobileService)
    {
        $this->mobileService = $mobileService;
    }

    /**
     * Get available payment methods for mobile
     */
    public function getPaymentMethods(Request $request): JsonResponse
    {
        try {
            // Mock payment methods for now
            $paymentMethods = [
                'success' => true,
                'payment_methods' => [
                    [
                        'id' => 'stripe',
                        'name' => 'Credit/Debit Card',
                        'type' => 'card',
                        'enabled' => true,
                        'icon' => 'credit-card'
                    ],
                    [
                        'id' => 'paypal',
                        'name' => 'PayPal',
                        'type' => 'paypal',
                        'enabled' => true,
                        'icon' => 'paypal'
                    ],
                    [
                        'id' => 'crypto',
                        'name' => 'Cryptocurrency',
                        'type' => 'crypto',
                        'enabled' => true,
                        'icon' => 'bitcoin'
                    ],
                    [
                        'id' => 'wallet',
                        'name' => 'Wallet Balance',
                        'type' => 'wallet',
                        'enabled' => true,
                        'icon' => 'wallet'
                    ]
                ]
            ];

            return response()->json($paymentMethods, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment methods',
                'error_code' => 'PAYMENT_METHODS_ERROR'
            ], 500);
        }
    }

    /**
     * Process payment from mobile
     */
    public function processPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'payment_method' => 'required|string|in:stripe,paypal,crypto,wallet',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = Auth::id();
            $paymentData = $request->all();
            $deviceId = $request->header('X-Device-ID', 'unknown');

            $result = $this->mobileService->processMobilePayment($paymentData, $userId, $deviceId);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error_code' => 'PAYMENT_PROCESSING_ERROR'
            ], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, $paymentId): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Mock payment status for now
            return response()->json([
                'success' => true,
                'payment' => [
                    'id' => $paymentId,
                    'status' => 'completed',
                    'amount' => 29.99,
                    'currency' => 'USD',
                    'payment_method' => 'stripe',
                    'processed_at' => now()->toISOString(),
                    'transaction_id' => 'txn_' . $paymentId
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'error_code' => 'PAYMENT_NOT_FOUND'
            ], 404);
        }
    }

    /**
     * Get user wallet balance
     */
    public function getWalletBalance(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Mock wallet balance for now
            return response()->json([
                'success' => true,
                'wallet' => [
                    'balance' => 125.50,
                    'currency' => 'USD',
                    'pending_balance' => 15.00,
                    'last_transaction' => [
                        'id' => 12345,
                        'type' => 'credit',
                        'amount' => 50.00,
                        'description' => 'Wallet top-up',
                        'created_at' => now()->subDays(2)->toISOString()
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch wallet balance',
                'error_code' => 'WALLET_ERROR'
            ], 500);
        }
    }

    /**
     * Add funds to wallet
     */
    public function addFundsToWallet(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:5|max:1000',
            'payment_method' => 'required|string|in:stripe,paypal,crypto',
            'payment_data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = Auth::id();
            $amount = $request->input('amount');

            // Mock wallet top-up for now
            return response()->json([
                'success' => true,
                'message' => 'Funds added successfully',
                'transaction' => [
                    'id' => rand(10000, 99999),
                    'amount' => $amount,
                    'currency' => 'USD',
                    'status' => 'pending',
                    'payment_method' => $request->input('payment_method'),
                    'created_at' => now()->toISOString()
                ],
                'new_balance' => 125.50 + $amount
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add funds',
                'error_code' => 'WALLET_TOPUP_ERROR'
            ], 500);
        }
    }

    /**
     * Get wallet transaction history
     */
    public function getWalletTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'type' => 'nullable|string|in:credit,debit,all'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = Auth::id();

            // Mock transaction history for now
            return response()->json([
                'success' => true,
                'transactions' => [
                    [
                        'id' => 12345,
                        'type' => 'credit',
                        'amount' => 50.00,
                        'currency' => 'USD',
                        'description' => 'Wallet top-up',
                        'status' => 'completed',
                        'created_at' => now()->subDays(2)->toISOString()
                    ],
                    [
                        'id' => 12344,
                        'type' => 'debit',
                        'amount' => -29.99,
                        'currency' => 'USD',
                        'description' => 'Server plan purchase',
                        'status' => 'completed',
                        'created_at' => now()->subDays(5)->toISOString()
                    ]
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 2,
                    'last_page' => 1
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'error_code' => 'TRANSACTIONS_ERROR'
            ], 500);
        }
    }

    /**
     * Create payment intent for mobile
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'payment_method' => 'required|string|in:stripe,paypal',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = Auth::id();

            // Mock payment intent creation for now
            return response()->json([
                'success' => true,
                'payment_intent' => [
                    'id' => 'pi_' . uniqid(),
                    'client_secret' => 'pi_' . uniqid() . '_secret_' . uniqid(),
                    'amount' => $request->input('amount'),
                    'currency' => $request->input('currency'),
                    'status' => 'requires_payment_method',
                    'payment_method' => $request->input('payment_method')
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent',
                'error_code' => 'PAYMENT_INTENT_ERROR'
            ], 500);
        }
    }

    /**
     * Confirm payment for mobile
     */
    public function confirmPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = Auth::id();

            // Mock payment confirmation for now
            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'payment' => [
                    'id' => rand(10000, 99999),
                    'status' => 'succeeded',
                    'amount' => 29.99,
                    'currency' => 'USD',
                    'confirmed_at' => now()->toISOString()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment confirmation failed',
                'error_code' => 'PAYMENT_CONFIRMATION_ERROR'
            ], 500);
        }
    }

    /**
     * Get payment history for mobile
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'status' => 'nullable|string|in:pending,completed,failed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = Auth::id();

            // Mock payment history for now
            return response()->json([
                'success' => true,
                'payments' => [
                    [
                        'id' => 12345,
                        'order_id' => 67890,
                        'amount' => 29.99,
                        'currency' => 'USD',
                        'payment_method' => 'stripe',
                        'status' => 'completed',
                        'created_at' => now()->subDays(5)->toISOString(),
                        'completed_at' => now()->subDays(5)->toISOString()
                    ],
                    [
                        'id' => 12344,
                        'order_id' => 67889,
                        'amount' => 19.99,
                        'currency' => 'USD',
                        'payment_method' => 'paypal',
                        'status' => 'completed',
                        'created_at' => now()->subDays(15)->toISOString(),
                        'completed_at' => now()->subDays(15)->toISOString()
                    ]
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 2,
                    'last_page' => 1
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history',
                'error_code' => 'PAYMENT_HISTORY_ERROR'
            ], 500);
        }
    }
}
