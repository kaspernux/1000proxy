<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Services\MarketingAutomationService;
use Illuminate\Support\Facades\Log;

class MarketingAutomationManagement extends Component
{
    // Properties
    public $activeTab = 'overview';
    public $campaigns = [];
    public $workflows = [];
    public $segments = [];
    public $analytics = [];
    public $emailProviders = [];
    public $automationSettings = [];

    // Campaign creation properties
    public $newCampaign = [
        'name' => '',
        'subject' => '',
        'content' => '',
        'target_segment' => '',
        'schedule_at' => ''
    ];

    // Workflow properties
    public $selectedWorkflow = '';
    public $workflowEnabled = false;

    // Test email properties
    public $testEmail = '';
    public $testTemplate = '';

    // Modals
    public $showCampaignModal = false;
    public $showWorkflowModal = false;
    public $showTestModal = false;
    public $showAnalyticsModal = false;

    // Loading states
    public $loading = false;
    public $processingAction = '';

    // Success/Error messages
    public $successMessage = '';
    public $errorMessage = '';

    protected $marketingService;

    public function boot(MarketingAutomationService $marketingService)
    {
        $this->marketingService = $marketingService;
    }

    public function mount()
    {
        $this->loadInitialData();
    }

    /**
     * Load initial data for the dashboard
     */
    public function loadInitialData()
    {
        try {
            $this->loading = true;

            // Load marketing dashboard data
            $dashboard = $this->marketingService->getMarketingDashboard();

            if ($dashboard['success']) {
                $this->analytics = $dashboard['data'];
                $this->segments = $dashboard['data']['customer_segments'] ?? [];
                $this->workflows = $dashboard['data']['automation_workflows'] ?? [];
            }

            // Load email providers status
            $this->emailProviders = [
                'mailchimp' => ['status' => 'active', 'enabled' => true],
                'sendgrid' => ['status' => 'active', 'enabled' => true],
                'mailgun' => ['status' => 'inactive', 'enabled' => false],
                'aws_ses' => ['status' => 'active', 'enabled' => true],
                'brevo' => ['status' => 'inactive', 'enabled' => false]
            ];

        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to load dashboard data: ' . $e->getMessage();
            Log::error('Marketing dashboard loading failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
        }
    }

    /**
     * Initialize marketing automation system
     */
    public function initializeAutomation()
    {
        try {
            $this->loading = true;
            $this->processingAction = 'Initializing marketing automation...';

            $result = $this->marketingService->initializeMarketingAutomation();

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->loadInitialData(); // Refresh data
            } else {
                $this->errorMessage = $result['message'];
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Initialization failed: ' . $e->getMessage();
            Log::error('Marketing automation initialization failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    /**
     * Create new email campaign
     */
    public function createCampaign()
    {
        try {
            $this->validate([
                'newCampaign.name' => 'required|string|max:255',
                'newCampaign.subject' => 'required|string|max:255',
                'newCampaign.content' => 'required|string',
                'newCampaign.target_segment' => 'required|string'
            ]);

            $this->loading = true;
            $this->processingAction = 'Creating campaign...';

            $result = $this->marketingService->createEmailCampaign($this->newCampaign);

            if ($result['success']) {
                $this->successMessage = "Campaign created successfully! Audience: {$result['audience_size']} users";
                $this->resetCampaignForm();
                $this->showCampaignModal = false;
                $this->loadInitialData(); // Refresh data
            } else {
                $this->errorMessage = $result['message'];
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Campaign creation failed: ' . $e->getMessage();
            Log::error('Campaign creation failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    /**
     * Execute marketing campaign
     */
    public function executeCampaign($campaignType)
    {
        try {
            $this->loading = true;
            $this->processingAction = "Executing {$campaignType} campaign...";

            $result = $this->marketingService->executeMarketingCampaign($campaignType);

            if ($result['success']) {
                $this->successMessage = "Campaign executed! Emails sent: {$result['emails_sent']}, Users targeted: {$result['users_targeted']}";
                $this->loadInitialData(); // Refresh data
            } else {
                $this->errorMessage = $result['message'];
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Campaign execution failed: ' . $e->getMessage();
            Log::error('Campaign execution failed', [
                'campaign_type' => $campaignType,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    /**
     * Process abandoned cart recovery
     */
    public function processAbandonedCarts()
    {
        try {
            $this->loading = true;
            $this->processingAction = 'Processing abandoned carts...';

            $result = $this->marketingService->processAbandonedCartRecovery();

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->loadInitialData(); // Refresh data
            } else {
                $this->errorMessage = $result['message'];
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Abandoned cart processing failed: ' . $e->getMessage();
            Log::error('Abandoned cart processing failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    /**
     * Test email delivery
     */
    public function testEmailDelivery()
    {
        try {
            $this->validate([
                'testEmail' => 'required|email',
                'testTemplate' => 'required|string'
            ]);

            $this->loading = true;
            $this->processingAction = 'Sending test email...';

            $result = $this->marketingService->testEmailDelivery($this->testEmail, $this->testTemplate);

            if ($result['success']) {
                $this->successMessage = $result['message'];
                $this->showTestModal = false;
            } else {
                $this->errorMessage = $result['message'];
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Email test failed: ' . $e->getMessage();
            Log::error('Email test failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    /**
     * Generate analytics report
     */
    public function generateAnalytics($dateRange = 30)
    {
        try {
            $this->loading = true;
            $this->processingAction = 'Generating analytics...';

            $analytics = $this->marketingService->generateMarketingAnalytics($dateRange);

            if (!isset($analytics['error'])) {
                $this->analytics = $analytics;
                $this->successMessage = 'Analytics generated successfully';
                $this->showAnalyticsModal = true;
            } else {
                $this->errorMessage = $analytics['error'];
            }

        } catch (\Exception $e) {
            $this->errorMessage = 'Analytics generation failed: ' . $e->getMessage();
            Log::error('Analytics generation failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    /**
     * Export campaign data
     */
    public function exportCampaignData($format = 'csv')
    {
        try {
            $this->loading = true;
            $this->processingAction = 'Exporting campaign data...';

            $export = $this->marketingService->exportCampaignData($format, 30);

            $this->successMessage = "Export completed! File size: {$export['size']}";

            // In a real implementation, this would trigger a download
            Log::info('Campaign data exported', ['export_url' => $export['url']]);

        } catch (\Exception $e) {
            $this->errorMessage = 'Export failed: ' . $e->getMessage();
            Log::error('Campaign export failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    /**
     * Toggle workflow status
     */
    public function toggleWorkflow($workflowName)
    {
        try {
            // Find and toggle the workflow
            foreach ($this->workflows as &$workflow) {
                if ($workflow['name'] === $workflowName) {
                    $workflow['enabled'] = !$workflow['enabled'];
                    $workflow['status'] = $workflow['enabled'] ? 'active' : 'paused';
                    break;
                }
            }

            $this->successMessage = "Workflow {$workflowName} " . ($workflow['enabled'] ? 'enabled' : 'disabled');

        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to toggle workflow: ' . $e->getMessage();
        }
    }

    /**
     * Reset campaign form
     */
    public function resetCampaignForm()
    {
        $this->newCampaign = [
            'name' => '',
            'subject' => '',
            'content' => '',
            'target_segment' => '',
            'schedule_at' => ''
        ];
    }

    /**
     * Clear messages
     */
    public function clearMessages()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    /**
     * Change active tab
     */
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->clearMessages();
    }

    /**
     * Open campaign creation modal
     */
    public function openCampaignModal()
    {
        $this->resetCampaignForm();
        $this->showCampaignModal = true;
        $this->clearMessages();
    }

    /**
     * Open test email modal
     */
    public function openTestModal()
    {
        $this->testEmail = '';
        $this->testTemplate = '';
        $this->showTestModal = true;
        $this->clearMessages();
    }

    /**
     * Get campaign performance metrics for display
     */
    public function getCampaignMetrics()
    {
        return $this->analytics['campaign_performance'] ?? [
            'total_campaigns' => 0,
            'emails_sent' => 0,
            'open_rate' => 0,
            'click_rate' => 0,
            'conversion_rate' => 0
        ];
    }

    /**
     * Get email metrics for display
     */
    public function getEmailMetrics()
    {
        return $this->analytics['email_metrics'] ?? [
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'bounced' => 0
        ];
    }

    /**
     * Get conversion rates for display
     */
    public function getConversionRates()
    {
        return $this->analytics['conversion_funnels'] ?? [
            'email_to_website' => 0,
            'website_to_cart' => 0,
            'cart_to_purchase' => 0
        ];
    }

    public function render()
    {
        return view('livewire.admin.marketing-automation-management');
    }
}
