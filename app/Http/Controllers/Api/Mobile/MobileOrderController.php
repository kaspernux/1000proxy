<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\MobileAppDevelopmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile Order Controller
 *
 * Handles mobile order management and tracking
 */
class MobileOrderController extends Controller
{
    protected $mobileService;

    public function __construct(MobileAppDevelopmentService $mobileService)
    {
        $this->mobileService = $mobileService;
    }

    /**
     * Get user orders for mobile
     */
    public function getUserOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|array',
            'status.*' => 'string|in:pending,processing,active,completed,cancelled,expired',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:30'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $filters = $request->only(['status', 'date_range']);
        $pagination = $request->only(['page', 'per_page']);

        $result = $this->mobileService->getMobileUserOrders($userId, $filters, $pagination);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get specific order details for mobile
     */
    public function getOrderDetails(Request $request, $orderId): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Mock order details for now
            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $orderId,
                    'order_number' => 'ORD-' . $orderId,
                    'status' => 'active',
                    'total_amount' => 29.99,
                    'currency' => 'USD',
                    'server_plan' => [
                        'name' => 'Premium US Server',
                        'location' => 'United States',
                        'protocol' => 'VLESS'
                    ],
                    'configuration' => [
                        'server' => 'us1.1000proxy.com',
                        'port' => 443,
                        'uuid' => '12345678-1234-1234-1234-123456789012'
                    ],
                    'qr_code_url' => url('/qr-codes/' . $orderId),
                    'expires_at' => now()->addDays(30)->toISOString(),
                    'created_at' => now()->subDays(5)->toISOString()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'error_code' => 'ORDER_NOT_FOUND'
            ], 404);
        }
    }

    /**
     * Create new order from mobile
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'server_plan_id' => 'required|integer|exists:server_plans,id',
            'duration_days' => 'nullable|integer|min:1|max:365',
            'payment_method' => 'required|string|in:stripe,paypal,crypto,wallet',
            'auto_renew' => 'nullable|boolean'
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
            $orderData = $request->all();

            // Mock order creation for now
            $orderId = rand(10000, 99999);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => [
                    'id' => $orderId,
                    'order_number' => 'ORD-' . $orderId,
                    'status' => 'pending',
                    'total_amount' => 29.99,
                    'currency' => 'USD',
                    'payment_required' => true,
                    'payment_url' => url('/mobile/payments/' . $orderId)
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error_code' => 'ORDER_CREATION_FAILED'
            ], 500);
        }
    }

    /**
     * Cancel order from mobile
     */
    public function cancelOrder(Request $request, $orderId): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Mock order cancellation for now
            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'order_id' => $orderId,
                'status' => 'cancelled',
                'refund_info' => [
                    'refund_amount' => 29.99,
                    'refund_method' => 'original_payment_method',
                    'estimated_days' => '3-5 business days'
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error_code' => 'CANCELLATION_FAILED'
            ], 500);
        }
    }

    /**
     * Get order configuration for mobile
     */
    public function getOrderConfiguration(Request $request, $orderId): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Mock configuration for now
            return response()->json([
                'success' => true,
                'configuration' => [
                    'order_id' => $orderId,
                    'server_details' => [
                        'host' => 'us1.1000proxy.com',
                        'port' => 443,
                        'protocol' => 'VLESS',
                        'uuid' => '12345678-1234-1234-1234-123456789012',
                        'encryption' => 'none'
                    ],
                    'connection_string' => 'vless://12345678-1234-1234-1234-123456789012@us1.1000proxy.com:443',
                    'qr_code_url' => url('/qr-codes/' . $orderId),
                    'setup_instructions' => [
                        'Download a VLESS-compatible client',
                        'Import the configuration using the QR code',
                        'Or manually enter the connection details',
                        'Test the connection'
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not available',
                'error_code' => 'CONFIG_NOT_AVAILABLE'
            ], 404);
        }
    }

    /**
     * Download QR code for mobile
     */
    public function downloadQRCode(Request $request, $orderId): JsonResponse
    {
        try {
            $userId = Auth::id();

            return response()->json([
                'success' => true,
                'qr_code' => [
                    'order_id' => $orderId,
                    'download_url' => url('/qr-codes/' . $orderId . '/download'),
                    'base64_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                    'expires_at' => now()->addHours(24)->toISOString()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'QR code not available',
                'error_code' => 'QR_NOT_AVAILABLE'
            ], 404);
        }
    }

    /**
     * Renew order from mobile
     */
    public function renewOrder(Request $request, $orderId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'duration_days' => 'nullable|integer|min:1|max:365',
            'payment_method' => 'required|string|in:stripe,paypal,crypto,wallet'
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

            return response()->json([
                'success' => true,
                'message' => 'Order renewal initiated',
                'renewal' => [
                    'original_order_id' => $orderId,
                    'new_order_id' => rand(10000, 99999),
                    'renewal_amount' => 29.99,
                    'extends_until' => now()->addDays(60)->toISOString(),
                    'payment_required' => true
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew order',
                'error_code' => 'RENEWAL_FAILED'
            ], 500);
        }
    }
}
