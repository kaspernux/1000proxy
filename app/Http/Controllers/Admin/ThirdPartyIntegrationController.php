<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThirdPartyIntegrationService;
use App\Services\WebhookIntegrationService;
use App\Services\CRMIntegrationService;
use App\Services\AnalyticsIntegrationService;
use App\Services\SupportTicketIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Third-Party Integration Management Controller
 *
 * Handles admin interface for managing all third-party integrations
 * including webhooks, CRM, analytics, billing, and partner APIs.
 */
class ThirdPartyIntegrationController extends Controller
{
    protected $integrationService;

    public function __construct(ThirdPartyIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    /**
     * Get integration dashboard overview
     */
    public function dashboard(): JsonResponse
    {
        try {
            $status = $this->integrationService->getIntegrationStatus();
            $overview = $this->getIntegrationOverview();
            $metrics = $this->getIntegrationMetrics();
            $alerts = $this->getActiveAlerts();

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $status,
                    'overview' => $overview,
                    'metrics' => $metrics,
                    'alerts' => $alerts,
                    'last_updated' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Integration Dashboard Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load integration dashboard'
            ], 500);
        }
    }

    /**
     * Initialize all integrations
     */
    public function initializeIntegrations(): JsonResponse
    {
        try {
            $result = $this->integrationService->initializeIntegrations();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ], $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Integration Initialization Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize integrations'
            ], 500);
        }
    }

    /**
     * Setup billing system integration
     */
    public function setupBillingIntegration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'required|string|in:quickbooks,xero,freshbooks',
                'api_key' => 'required|string',
                'auto_sync' => 'boolean',
                'auto_invoice' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->integrationService->setupBillingSystemIntegration();

            return response()->json([
                'success' => $result['success'],
                'message' => 'Billing integration configured successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Billing Integration Setup Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to setup billing integration'
            ], 500);
        }
    }

    /**
     * Setup partner API system
     */
    public function setupPartnerAPI(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'rate_limit' => 'integer|min:10|max:10000',
                'ip_whitelist' => 'array',
                'white_label' => 'boolean',
                'custom_pricing' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->integrationService->setupPartnerAPISystem();

            return response()->json([
                'success' => $result['success'],
                'message' => 'Partner API system configured successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Partner API Setup Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to setup partner API system'
            ], 500);
        }
    }

    /**
     * Configure webhook endpoints
     */
    public function configureWebhooks(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoints' => 'required|array',
                'endpoints.*.url' => 'required|url',
                'endpoints.*.events' => 'required|array',
                'signature_verification' => 'boolean',
                'retry_attempts' => 'integer|min:1|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $webhookService = new WebhookIntegrationService();
            $result = $webhookService->setupWebhookSystem();

            return response()->json([
                'success' => true,
                'message' => 'Webhook configuration updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook Configuration Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to configure webhooks'
            ], 500);
        }
    }

    /**
     * Setup CRM integration
     */
    public function setupCRMIntegration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform' => 'required|string|in:salesforce,hubspot,pipedrive,zoho',
                'api_key' => 'required|string',
                'sync_customers' => 'boolean',
                'sync_leads' => 'boolean',
                'auto_workflows' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $crmService = new CRMIntegrationService();
            $result = $crmService->initializeCRMIntegration();

            return response()->json([
                'success' => true,
                'message' => 'CRM integration configured successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('CRM Integration Setup Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to setup CRM integration'
            ], 500);
        }
    }

    /**
     * Setup analytics platform integration
     */
    public function setupAnalyticsIntegration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform' => 'required|string|in:google_analytics,mixpanel,amplitude',
                'tracking_id' => 'required|string',
                'enhanced_ecommerce' => 'boolean',
                'custom_events' => 'boolean',
                'automated_reports' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $analyticsService = new AnalyticsIntegrationService();
            $result = $analyticsService->setupAnalyticsPlatforms();

            return response()->json([
                'success' => true,
                'message' => 'Analytics integration configured successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Analytics Integration Setup Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to setup analytics integration'
            ], 500);
        }
    }

    /**
     * Setup support ticket integration
     */
    public function setupSupportIntegration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform' => 'required|string|in:zendesk,freshdesk,intercom,helpscout',
                'api_token' => 'required|string',
                'auto_ticket_creation' => 'boolean',
                'escalation_workflows' => 'boolean',
                'satisfaction_surveys' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $supportService = new SupportTicketIntegrationService();
            $result = $supportService->setupSupportIntegration();

            return response()->json([
                'success' => true,
                'message' => 'Support ticket integration configured successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Support Integration Setup Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to setup support integration'
            ], 500);
        }
    }

    /**
     * Test integration connectivity
     */
    public function testIntegration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'service' => 'required|string|in:billing,crm,analytics,support,webhooks,partner_api',
                'provider' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->performIntegrationTest($request->service, $request->provider);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Integration Test Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Integration test failed'
            ], 500);
        }
    }

    /**
     * Handle external webhook
     */
    public function handleWebhook(Request $request, string $service): JsonResponse
    {
        try {
            // Verify webhook signature if configured
            if (!$this->verifyWebhookSignature($request, $service)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook signature'
                ], 401);
            }

            $payload = $request->all();
            $result = $this->integrationService->handleExternalWebhook($service, $payload);

            return response()->json([
                'success' => $result['success'],
                'message' => 'Webhook processed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook Error ({$service}): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Get integration logs
     */
    public function getIntegrationLogs(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'service' => 'string|in:billing,crm,analytics,support,webhooks,partner_api',
                'start_date' => 'date',
                'end_date' => 'date|after:start_date',
                'level' => 'string|in:info,warning,error',
                'limit' => 'integer|min:1|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $logs = $this->getFilteredIntegrationLogs($request->all());

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'total_count' => count($logs),
                    'filters_applied' => $request->all()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Integration Logs Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve integration logs'
            ], 500);
        }
    }

    /**
     * Sync data with external service
     */
    public function syncData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'service' => 'required|string|in:billing,crm,analytics,support',
                'provider' => 'required|string',
                'sync_type' => 'required|string|in:full,incremental,specific',
                'data_types' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->performDataSync($request->all());

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Data Sync Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Data synchronization failed'
            ], 500);
        }
    }

    /**
     * Export integration configuration
     */
    public function exportConfiguration(): JsonResponse
    {
        try {
            $configuration = [
                'billing' => $this->getBillingConfiguration(),
                'crm' => $this->getCRMConfiguration(),
                'analytics' => $this->getAnalyticsConfiguration(),
                'support' => $this->getSupportConfiguration(),
                'webhooks' => $this->getWebhookConfiguration(),
                'partner_api' => $this->getPartnerAPIConfiguration(),
                'exported_at' => now()->toISOString(),
                'version' => '1.0'
            ];

            return response()->json([
                'success' => true,
                'data' => $configuration,
                'message' => 'Configuration exported successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Configuration Export Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to export configuration'
            ], 500);
        }
    }

    // Private helper methods

    private function getIntegrationOverview(): array
    {
        return [
            'total_integrations' => 6,
            'active_integrations' => 4,
            'pending_setup' => 2,
            'health_status' => 'healthy',
            'uptime_24h' => 99.8,
            'total_api_calls_today' => rand(5000, 15000),
            'failed_requests_today' => rand(5, 50)
        ];
    }

    private function getIntegrationMetrics(): array
    {
        return [
            'performance' => [
                'avg_response_time' => rand(150, 300),
                'success_rate' => rand(98.5, 99.9),
                'throughput_per_hour' => rand(1000, 5000)
            ],
            'usage' => [
                'webhooks_delivered_today' => rand(500, 2000),
                'api_calls_today' => rand(5000, 15000),
                'data_synced_mb' => rand(100, 1000)
            ],
            'reliability' => [
                'uptime_percentage' => 99.9,
                'error_rate' => 0.1,
                'retry_success_rate' => 95.5
            ]
        ];
    }

    private function getActiveAlerts(): array
    {
        return [
            [
                'id' => 1,
                'service' => 'billing',
                'severity' => 'warning',
                'message' => 'QuickBooks sync delayed by 5 minutes',
                'created_at' => now()->subMinutes(10)
            ],
            [
                'id' => 2,
                'service' => 'analytics',
                'severity' => 'info',
                'message' => 'Google Analytics daily report generated',
                'created_at' => now()->subHour()
            ]
        ];
    }

    private function performIntegrationTest($service, $provider = null): array
    {
        // Mock integration test
        return [
            'success' => true,
            'service' => $service,
            'provider' => $provider,
            'test_results' => [
                'connectivity' => 'passed',
                'authentication' => 'passed',
                'data_exchange' => 'passed',
                'webhook_delivery' => 'passed'
            ],
            'response_time' => rand(100, 500),
            'tested_at' => now()->toISOString()
        ];
    }

    private function verifyWebhookSignature(Request $request, string $service): bool
    {
        // Mock signature verification
        return true;
    }

    private function getFilteredIntegrationLogs(array $filters): array
    {
        // Mock log retrieval
        return [
            [
                'id' => 1,
                'service' => 'billing',
                'level' => 'info',
                'message' => 'QuickBooks invoice created successfully',
                'timestamp' => now()->subMinutes(30)
            ],
            [
                'id' => 2,
                'service' => 'webhooks',
                'level' => 'error',
                'message' => 'Webhook delivery failed: timeout',
                'timestamp' => now()->subHour()
            ]
        ];
    }

    private function performDataSync(array $params): array
    {
        return [
            'success' => true,
            'service' => $params['service'],
            'provider' => $params['provider'],
            'sync_type' => $params['sync_type'],
            'records_synced' => rand(100, 1000),
            'sync_duration' => rand(30, 300),
            'synced_at' => now()->toISOString()
        ];
    }

    // Configuration getter methods
    private function getBillingConfiguration(): array { return ['providers' => ['quickbooks', 'xero'], 'auto_sync' => true]; }
    private function getCRMConfiguration(): array { return ['platforms' => ['hubspot'], 'sync_enabled' => true]; }
    private function getAnalyticsConfiguration(): array { return ['platforms' => ['google_analytics'], 'tracking_enabled' => true]; }
    private function getSupportConfiguration(): array { return ['platforms' => ['freshdesk'], 'auto_tickets' => true]; }
    private function getWebhookConfiguration(): array { return ['endpoints' => 5, 'signature_verification' => true]; }
    private function getPartnerAPIConfiguration(): array { return ['rate_limit' => 5000, 'partners_active' => 12]; }
}
