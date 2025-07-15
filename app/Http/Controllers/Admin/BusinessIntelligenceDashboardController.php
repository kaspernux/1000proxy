<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BusinessIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Business Intelligence Dashboard Controller
 *
 * Provides analytics and reporting functionality for business intelligence
 */
class BusinessIntelligenceDashboardController extends Controller
{
    protected $biService;

    public function __construct(BusinessIntelligenceService $biService)
    {
        $this->biService = $biService;
    }

    /**
     * Display the main BI dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('range', '30_days');
        $analytics = $this->biService->getDashboardAnalytics($dateRange);

        return view('admin.business-intelligence.dashboard', [
            'analytics' => $analytics,
            'dateRange' => $dateRange
        ]);
    }

    /**
     * Get dashboard analytics via API
     */
    public function getAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range' => 'nullable|string|in:7_days,30_days,90_days,1_year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $request->get('range', '30_days');
        $analytics = $this->biService->getDashboardAnalytics($dateRange);

        return response()->json($analytics);
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range' => 'nullable|string|in:7_days,30_days,90_days,1_year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $request->get('range', '30_days');
        $period = $this->parseDateRange($dateRange);
        $revenueData = $this->biService->getRevenueAnalytics($period);

        return response()->json([
            'success' => true,
            'data' => $revenueData
        ]);
    }

    /**
     * Get user analytics
     */
    public function getUserAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range' => 'nullable|string|in:7_days,30_days,90_days,1_year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $request->get('range', '30_days');
        $period = $this->parseDateRange($dateRange);
        $userData = $this->biService->getUserAnalytics($period);

        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
    }

    /**
     * Get order analytics
     */
    public function getOrderAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range' => 'nullable|string|in:7_days,30_days,90_days,1_year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $request->get('range', '30_days');
        $period = $this->parseDateRange($dateRange);
        $orderData = $this->biService->getOrderAnalytics($period);

        return response()->json([
            'success' => true,
            'data' => $orderData
        ]);
    }

    /**
     * Get server analytics
     */
    public function getServerAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range' => 'nullable|string|in:7_days,30_days,90_days,1_year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $request->get('range', '30_days');
        $period = $this->parseDateRange($dateRange);
        $serverData = $this->biService->getServerAnalytics($period);

        return response()->json([
            'success' => true,
            'data' => $serverData
        ]);
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range' => 'nullable|string|in:7_days,30_days,90_days,1_year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $request->get('range', '30_days');
        $period = $this->parseDateRange($dateRange);
        $performanceData = $this->biService->getPerformanceMetrics($period);

        return response()->json([
            'success' => true,
            'data' => $performanceData
        ]);
    }

    /**
     * Generate automated insights
     */
    public function generateInsights(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range' => 'nullable|string|in:7_days,30_days,90_days,1_year'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $request->get('range', '30_days');
        $insights = $this->biService->generateInsights(['range' => $dateRange]);

        return response()->json($insights);
    }

    /**
     * Get churn prediction
     */
    public function getChurnPrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->get('user_id');
        $churnData = $this->biService->getChurnPrediction($userId);

        return response()->json($churnData);
    }

    /**
     * Export analytics data
     */
    public function exportAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'range' => 'nullable|string|in:7_days,30_days,90_days,1_year',
            'format' => 'nullable|string|in:csv,xlsx,pdf',
            'sections' => 'nullable|array',
            'sections.*' => 'string|in:revenue,users,orders,servers,performance'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $request->get('range', '30_days');
        $format = $request->get('format', 'csv');
        $sections = $request->get('sections', ['revenue', 'users', 'orders']);

        // For now, return a mock export URL
        return response()->json([
            'success' => true,
            'export_url' => url('/admin/bi/exports/' . uniqid() . '.' . $format),
            'expires_at' => now()->addHours(24)->toISOString()
        ]);
    }

    /**
     * Get real-time metrics
     */
    public function getRealTimeMetrics(Request $request)
    {
        // Mock real-time metrics
        return response()->json([
            'success' => true,
            'data' => [
                'active_users' => rand(50, 200),
                'current_orders' => rand(5, 25),
                'server_load' => rand(30, 80),
                'revenue_today' => rand(500, 2000),
                'last_updated' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get alert notifications
     */
    public function getAlerts(Request $request)
    {
        // Mock alert notifications
        return response()->json([
            'success' => true,
            'alerts' => [
                [
                    'id' => 1,
                    'type' => 'warning',
                    'title' => 'High Server Load',
                    'message' => 'Server US-East-1 is experiencing high CPU usage (85%)',
                    'created_at' => now()->subMinutes(15)->toISOString()
                ],
                [
                    'id' => 2,
                    'type' => 'info',
                    'title' => 'Revenue Milestone',
                    'message' => 'Monthly revenue target reached with 5 days remaining',
                    'created_at' => now()->subHours(2)->toISOString()
                ]
            ]
        ]);
    }

    /**
     * Helper method to parse date range
     */
    private function parseDateRange($range): array
    {
        $end = now();

        switch ($range) {
            case '7_days':
                $start = $end->copy()->subDays(7);
                break;
            case '30_days':
                $start = $end->copy()->subDays(30);
                break;
            case '90_days':
                $start = $end->copy()->subDays(90);
                break;
            case '1_year':
                $start = $end->copy()->subYear();
                break;
            default:
                $start = $end->copy()->subDays(30);
        }

        return [
            'start' => $start,
            'end' => $end,
            'range' => $range
        ];
    }
}
