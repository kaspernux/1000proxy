<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\MarketingAutomationService;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use BackedEnum;

class MarketingAutomationManagement extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Marketing Automation';
    protected static ?string $title = 'Marketing Automation Management';
    protected static ?string $slug = 'marketing-automation-management';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.admin.pages.marketing-automation-management';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasStaffPermission('manage_settings') || $user->isAdmin() || $user->isManager());
    }

    // State properties mirrored from Livewire component
    public string $activeTab = 'overview';
    public array $campaigns = [];
    public array $workflows = [];
    public array $segments = [];
    public array $analytics = [];
    public array $emailProviders = [];
    public array $automationSettings = [];

    public array $newCampaign = [
        'name' => '',
        'subject' => '',
        'content' => '',
        'target_segment' => '',
        'schedule_at' => '',
    ];

    public string $selectedWorkflow = '';
    public bool $workflowEnabled = false;

    public string $testEmail = '';
    public string $testTemplate = '';

    public bool $showCampaignModal = false;
    public bool $showWorkflowModal = false;
    public bool $showTestModal = false;
    public bool $showAnalyticsModal = false;

    public bool $loading = false;
    public string $processingAction = '';

    public string $successMessage = '';
    public string $errorMessage = '';

    protected MarketingAutomationService $marketingService;

    public function boot(MarketingAutomationService $marketingService): void
    {
        $this->marketingService = $marketingService;
    }

    public function mount(): void
    {
        $this->loadInitialData();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->loadInitialData();
                    Notification::make()->title('Dashboard refreshed')->success()->send();
                }),
            \Filament\Actions\Action::make('help')
                ->label('Help')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalHeading('Marketing Automation')
                ->modalContent(new \Illuminate\Support\HtmlString('Configure email providers and automate customer outreach. Create campaigns and workflows, then monitor performance here.'))
                ->modalSubmitAction(false),
        ];
    }

    public function loadInitialData(): void
    {
        try {
            $this->loading = true;

            $dashboard = $this->marketingService->getMarketingDashboard();

            if (($dashboard['success'] ?? false) === true) {
                $this->analytics = $dashboard['data'];
                $this->segments = $dashboard['data']['customer_segments'] ?? [];
                $this->workflows = $dashboard['data']['automation_workflows'] ?? [];
            }

            $this->emailProviders = [
                'mailchimp' => ['status' => 'active', 'enabled' => true],
                'sendgrid' => ['status' => 'active', 'enabled' => true],
                'mailgun' => ['status' => 'inactive', 'enabled' => false],
                'aws_ses' => ['status' => 'active', 'enabled' => true],
                'brevo' => ['status' => 'inactive', 'enabled' => false],
            ];
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to load dashboard data: ' . $e->getMessage();
            Log::error('Marketing dashboard loading failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
        }
    }

    public function initializeAutomation(): void
    {
        try {
            $this->loading = true;
            $this->processingAction = 'Initializing marketing automation...';

            $result = $this->marketingService->initializeMarketingAutomation();

            if (($result['success'] ?? false) === true) {
                $this->successMessage = $result['message'] ?? 'Initialized';
                Notification::make()->title($this->successMessage)->success()->send();
                $this->loadInitialData();
            } else {
                $this->errorMessage = $result['message'] ?? 'Initialization failed';
                Notification::make()->title('Initialization failed')->danger()->body($this->errorMessage)->send();
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Initialization failed: ' . $e->getMessage();
            Log::error('Marketing automation initialization failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Initialization failed')->danger()->body($e->getMessage())->send();
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    public function createCampaign(): void
    {
        try {
            $this->validate([
                'newCampaign.name' => 'required|string|max:255',
                'newCampaign.subject' => 'required|string|max:255',
                'newCampaign.content' => 'required|string',
                'newCampaign.target_segment' => 'required|string',
            ]);

            $this->loading = true;
            $this->processingAction = 'Creating campaign...';

            $result = $this->marketingService->createEmailCampaign($this->newCampaign);

            if (($result['success'] ?? false) === true) {
                $audience = $result['audience_size'] ?? 0;
                $this->successMessage = "Campaign created successfully! Audience: {$audience} users";
                Notification::make()->title('Campaign created')->body("Audience: {$audience} users")->success()->send();
                $this->resetCampaignForm();
                $this->showCampaignModal = false;
                $this->loadInitialData();
            } else {
                $this->errorMessage = $result['message'] ?? 'Campaign creation failed';
                Notification::make()->title('Campaign creation failed')->danger()->body($this->errorMessage)->send();
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Campaign creation failed: ' . $e->getMessage();
            Log::error('Campaign creation failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Campaign creation failed')->danger()->body($e->getMessage())->send();
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    public function executeCampaign(string $campaignType): void
    {
        try {
            $this->loading = true;
            $this->processingAction = "Executing {$campaignType} campaign...";

            $result = $this->marketingService->executeMarketingCampaign($campaignType);

            if (($result['success'] ?? false) === true) {
                $emails = $result['emails_sent'] ?? 0;
                $users = $result['users_targeted'] ?? 0;
                $this->successMessage = "Campaign executed! Emails sent: {$emails}, Users targeted: {$users}";
                Notification::make()->title('Campaign executed')->body("Emails: {$emails}, Users: {$users}")->success()->send();
                $this->loadInitialData();
            } else {
                $this->errorMessage = $result['message'] ?? 'Campaign execution failed';
                Notification::make()->title('Campaign execution failed')->danger()->body($this->errorMessage)->send();
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Campaign execution failed: ' . $e->getMessage();
            Log::error('Campaign execution failed', [
                'campaign_type' => $campaignType,
                'error' => $e->getMessage(),
            ]);
            Notification::make()->title('Campaign execution failed')->danger()->body($e->getMessage())->send();
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    public function processAbandonedCarts(): void
    {
        try {
            $this->loading = true;
            $this->processingAction = 'Processing abandoned carts...';

            $result = $this->marketingService->processAbandonedCartRecovery();

            if (($result['success'] ?? false) === true) {
                $this->successMessage = $result['message'] ?? 'Processed';
                Notification::make()->title('Abandoned carts processed')->success()->send();
                $this->loadInitialData();
            } else {
                $this->errorMessage = $result['message'] ?? 'Processing failed';
                Notification::make()->title('Abandoned cart processing failed')->danger()->body($this->errorMessage)->send();
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Abandoned cart processing failed: ' . $e->getMessage();
            Log::error('Abandoned cart processing failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Abandoned cart processing failed')->danger()->body($e->getMessage())->send();
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    public function testEmailDelivery(): void
    {
        try {
            $this->validate([
                'testEmail' => 'required|email',
                'testTemplate' => 'required|string',
            ]);

            $this->loading = true;
            $this->processingAction = 'Sending test email...';

            $result = $this->marketingService->testEmailDelivery($this->testEmail, $this->testTemplate);

            if (($result['success'] ?? false) === true) {
                $this->successMessage = $result['message'] ?? 'Test sent';
                $this->showTestModal = false;
                Notification::make()->title('Test email sent')->success()->send();
            } else {
                $this->errorMessage = $result['message'] ?? 'Test failed';
                Notification::make()->title('Test email failed')->danger()->body($this->errorMessage)->send();
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Email test failed: ' . $e->getMessage();
            Log::error('Email test failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Email test failed')->danger()->body($e->getMessage())->send();
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    public function generateAnalytics(int $dateRange = 30): void
    {
        try {
            $this->loading = true;
            $this->processingAction = 'Generating analytics...';

            $analytics = $this->marketingService->generateMarketingAnalytics($dateRange);

            if (!isset($analytics['error'])) {
                $this->analytics = $analytics;
                $this->successMessage = 'Analytics generated successfully';
                $this->showAnalyticsModal = true;
                Notification::make()->title('Analytics ready')->success()->send();
            } else {
                $this->errorMessage = $analytics['error'];
                Notification::make()->title('Analytics generation failed')->danger()->body($this->errorMessage)->send();
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Analytics generation failed: ' . $e->getMessage();
            Log::error('Analytics generation failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Analytics generation failed')->danger()->body($e->getMessage())->send();
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    public function exportCampaignData(string $format = 'csv'): void
    {
        try {
            $this->loading = true;
            $this->processingAction = 'Exporting campaign data...';

            $export = $this->marketingService->exportCampaignData($format, 30);

            $size = $export['size'] ?? '0B';
            $this->successMessage = "Export completed! File size: {$size}";
            Notification::make()->title('Export complete')->body("File size: {$size}")->success()->send();
            Log::info('Campaign data exported', ['export_url' => $export['url'] ?? null]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Export failed: ' . $e->getMessage();
            Log::error('Campaign export failed', ['error' => $e->getMessage()]);
            Notification::make()->title('Export failed')->danger()->body($e->getMessage())->send();
        } finally {
            $this->loading = false;
            $this->processingAction = '';
        }
    }

    public function toggleWorkflow(string $workflowName): void
    {
        try {
        foreach ($this->workflows as $index => $workflow) {
                if (($workflow['name'] ?? '') === $workflowName) {
                    $enabled = !((bool) ($workflow['enabled'] ?? false));
                    $this->workflows[$index]['enabled'] = $enabled;
                    $this->workflows[$index]['status'] = $enabled ? 'active' : 'paused';
                    $this->successMessage = "Workflow {$workflowName} " . ($enabled ? 'enabled' : 'disabled');
            Notification::make()->title($this->successMessage)->success()->send();
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to toggle workflow: ' . $e->getMessage();
        Notification::make()->title('Toggle workflow failed')->danger()->body($e->getMessage())->send();
        }
    }

    public function resetCampaignForm(): void
    {
        $this->newCampaign = [
            'name' => '',
            'subject' => '',
            'content' => '',
            'target_segment' => '',
            'schedule_at' => '',
        ];
    }

    public function clearMessages(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->clearMessages();
    }

    public function openCampaignModal(): void
    {
        $this->resetCampaignForm();
        $this->showCampaignModal = true;
        $this->clearMessages();
    }

    public function openTestModal(): void
    {
        $this->testEmail = '';
        $this->testTemplate = '';
        $this->showTestModal = true;
        $this->clearMessages();
    }

    public function getCampaignMetrics(): array
    {
        return $this->analytics['campaign_performance'] ?? [
            'total_campaigns' => 0,
            'emails_sent' => 0,
            'open_rate' => 0,
            'click_rate' => 0,
            'conversion_rate' => 0,
        ];
    }

    public function getEmailMetrics(): array
    {
        return $this->analytics['email_metrics'] ?? [
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'bounced' => 0,
        ];
    }

    public function getConversionRates(): array
    {
        return $this->analytics['conversion_funnels'] ?? [
            'email_to_website' => 0,
            'website_to_cart' => 0,
            'cart_to_purchase' => 0,
        ];
    }
}
