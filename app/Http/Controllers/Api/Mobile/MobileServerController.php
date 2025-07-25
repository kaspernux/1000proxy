<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\MobileAppDevelopmentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Mobile Server Controller
 *
 * Handles mobile server browsing and filtering
 */
class MobileServerController extends Controller
{
    protected $mobileService;

    public function __construct(MobileAppDevelopmentService $mobileService)
    {
        $this->mobileService = $mobileService;
    }

    /**
     * Get mobile-optimized server plans
     */
    public function getServerPlans(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'country' => 'nullable|string|size:2',
            'category' => 'nullable|string',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'protocols' => 'nullable|array',
            'protocols.*' => 'string|in:VLESS,VMESS,TROJAN,SHADOWSOCKS',
            'sort' => 'nullable|string|in:recommended,price_low,price_high,speed,popularity,newest',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = $request->only(['country', 'category', 'min_price', 'max_price', 'protocols', 'sort']);
        $pagination = $request->only(['page', 'per_page']);

        $result = $this->mobileService->getMobileServerPlans($filters, $pagination);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get available filters for mobile
     */
    public function getAvailableFilters(): JsonResponse
    {
        try {
            $filters = $this->mobileService->getMobileAvailableFilters();

            return response()->json([
                'success' => true,
                'filters' => $filters
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get filters',
                'error_code' => 'FILTERS_ERROR'
            ], 500);
        }
    }

    /**
     * Search servers for mobile
     */
    public function searchServers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:100',
            'filters' => 'nullable|array',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $searchQuery = $request->input('query');
        $filters = array_merge(
            $request->input('filters', []),
            ['search' => $searchQuery]
        );
        $pagination = $request->only(['page', 'per_page']);

        $result = $this->mobileService->getMobileServerPlans($filters, $pagination);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get server plan details for mobile
     */
    public function getServerPlanDetails(Request $request, $planId): JsonResponse
    {
        try {
            // Mock server plan details for now
            return response()->json([
                'success' => true,
                'server_plan' => [
                    'id' => $planId,
                    'name' => 'Premium US Server',
                    'description' => 'High-speed server in the United States',
                    'price' => 29.99,
                    'currency' => 'USD',
                    'features' => [
                        'Unlimited bandwidth',
                        'VLESS/VMESS protocols',
                        '24/7 support',
                        '99.9% uptime'
                    ],
                    'technical_specs' => [
                        'protocols' => ['VLESS', 'VMESS'],
                        'encryption' => 'AES-256',
                        'max_connections' => 5,
                        'bandwidth' => 'Unlimited'
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server plan not found',
                'error_code' => 'PLAN_NOT_FOUND'
            ], 404);
        }
    }

    /**
     * Get popular servers for mobile
     */
    public function getPopularServers(): JsonResponse
    {
        try {
            $filters = ['sort' => 'popularity'];
            $pagination = ['page' => 1, 'per_page' => 10];

            $result = $this->mobileService->getMobileServerPlans($filters, $pagination);

            return response()->json([
                'success' => true,
                'popular_servers' => $result['data'] ?? []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get popular servers',
                'error_code' => 'POPULAR_SERVERS_ERROR'
            ], 500);
        }
    }

    /**
     * Get recommended servers for mobile
     */
    public function getRecommendedServers(): JsonResponse
    {
        try {
            $filters = ['sort' => 'recommended'];
            $pagination = ['page' => 1, 'per_page' => 10];

            $result = $this->mobileService->getMobileServerPlans($filters, $pagination);

            return response()->json([
                'success' => true,
                'recommended_servers' => $result['data'] ?? []
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recommended servers',
                'error_code' => 'RECOMMENDED_SERVERS_ERROR'
            ], 500);
        }
    }
}
