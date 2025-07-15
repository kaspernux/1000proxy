<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MarketingAutomationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MarketingAutomationController extends Controller
{
    protected $marketingService;

    public function __construct(MarketingAutomationService $marketingService)
    {
        $this->marketingService = $marketingService;
    }

    /**
     * Get marketing automation dashboard
     */
    public function dashboard(): JsonResponse
    {
        try {
            $dashboard = $this->marketingService->getMarketingDashboard();

            return response()->json($dashboard);

        } catch (\Exception $e) {
            Log::error('Marketing dashboard failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize marketing automation system
     */
    public function initializeAutomation(): JsonResponse
    {
        try {
            $result = $this->marketingService->initializeMarketingAutomation();

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Marketing automation initialization failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Initialization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create email campaign
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'content' => 'required|string',
                'target_segment' => 'required|string',
                'schedule_at' => 'nullable|date|after:now'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->marketingService->createEmailCampaign($request->all());

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Campaign creation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Campaign creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Setup automated workflows
     */
    public function setupWorkflows(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'workflows' => 'required|array',
                'workflows.*.name' => 'required|string',
                'workflows.*.enabled' => 'required|boolean',
                'workflows.*.trigger' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->marketingService->setupAutomatedWorkflows();

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Workflow setup failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Workflow setup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process lead nurturing
     */
    public function processLeadNurturing(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'trigger_event' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->marketingService->processLeadNurturing(
                $request->user_id,
                $request->trigger_event
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Lead nurturing failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lead nurturing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process abandoned cart recovery
     */
    public function processAbandonedCart(): JsonResponse
    {
        try {
            $result = $this->marketingService->processAbandonedCartRecovery();

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Abandoned cart processing failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Abandoned cart processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer segments
     */
    public function getCustomerSegments(): JsonResponse
    {
        try {
            $segments = $this->marketingService->getCustomerSegments();

            return response()->json([
                'success' => true,
                'segments' => $segments
            ]);

        } catch (\Exception $e) {
            Log::error('Customer segments retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve segments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign performance
     */
    public function getCampaignPerformance(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->input('date_range', 30);

            $performance = $this->marketingService->getCampaignPerformance($dateRange);

            return response()->json([
                'success' => true,
                'performance' => $performance
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign performance retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get email metrics
     */
    public function getEmailMetrics(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->input('date_range', 30);

            $metrics = $this->marketingService->getEmailMetrics($dateRange);

            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);

        } catch (\Exception $e) {
            Log::error('Email metrics retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute marketing campaign
     */
    public function executeCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'campaign_type' => 'required|string|in:welcome_series,abandoned_cart,win_back,birthday,referral',
                'target_segment' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->marketingService->executeMarketingCampaign($request->campaign_type);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Campaign execution failed', [
                'error' => $e->getMessage(),
                'campaign_type' => $request->campaign_type ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Campaign execution failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update automation settings
     */
    public function updateAutomationSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.email_frequency' => 'nullable|string|in:daily,weekly,monthly',
                'settings.send_time' => 'nullable|string',
                'settings.timezone' => 'nullable|string',
                'settings.unsubscribe_handling' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->marketingService->updateAutomationSettings($request->settings);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Automation settings update failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Settings update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate marketing analytics
     */
    public function generateAnalytics(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->input('date_range', 30);

            $analytics = $this->marketingService->generateMarketingAnalytics($dateRange);

            return response()->json([
                'success' => true,
                'analytics' => $analytics
            ]);

        } catch (\Exception $e) {
            Log::error('Marketing analytics generation failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Analytics generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test email delivery
     */
    public function testEmailDelivery(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'template' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->marketingService->testEmailDelivery(
                $request->email,
                $request->template
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Email delivery test failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export campaign data
     */
    public function exportCampaignData(Request $request): JsonResponse
    {
        try {
            $format = $request->input('format', 'csv');
            $dateRange = $request->input('date_range', 30);

            $export = $this->marketingService->exportCampaignData($format, $dateRange);

            return response()->json([
                'success' => true,
                'export_url' => $export['url'],
                'file_size' => $export['size']
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign data export failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
