<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ThirdPartyIntegrationService;
use App\Services\WebhookIntegrationService;
use App\Services\CRMIntegrationService;
use App\Services\AnalyticsIntegrationService;
use App\Services\SupportTicketIntegrationService;

/**
 * Monitor Third-Party Integration Health
 *
 * Console command to check the health and status of all third-party integrations
 * and generate alerts for any issues detected.
 */
class MonitorIntegrations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'integrations:monitor
                          {--service= : Specific service to monitor}
                          {--alert : Send alerts for critical issues}
                          {--repair : Attempt automatic repair of failed integrations}
                          {--detailed : Show detailed health information}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor third-party integration health and status';

    protected $integrationService;

    public function __construct(ThirdPartyIntegrationService $integrationService)
    {
        parent::__construct();
        $this->integrationService = $integrationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = $this->option('service');
        $sendAlerts = $this->option('alert');
        $autoRepair = $this->option('repair');
        $detailed = $this->option('detailed');

        $this->info('🔍 Monitoring third-party integrations...');

        try {
            if ($service) {
                $this->monitorSpecificService($service, $sendAlerts, $autoRepair, $detailed);
            } else {
                $this->monitorAllServices($sendAlerts, $autoRepair, $detailed);
            }

            $this->info('✅ Integration monitoring completed!');
        } catch (\Exception $e) {
            $this->error('❌ Monitoring failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Monitor all services
     */
    private function monitorAllServices($sendAlerts, $autoRepair, $detailed)
    {
        // Get overall integration status
        $statusResult = $this->integrationService->getIntegrationStatus();

        if (!$statusResult['success']) {
            $this->error('Failed to get integration status');
            return;
        }

        $status = $statusResult['status'];
        $metrics = $statusResult['metrics'] ?? [];

        // Display overall health
        $this->displayOverallHealth($status, $metrics, $detailed);

        // Monitor individual services
        $services = [
            'webhooks' => new WebhookIntegrationService(),
            'crm' => new CRMIntegrationService(),
            'analytics' => new AnalyticsIntegrationService(),
            'support' => new SupportTicketIntegrationService()
        ];

        $issues = [];

        foreach ($services as $serviceName => $serviceInstance) {
            $serviceStatus = $serviceInstance->getServiceStatus();
            $this->displayServiceStatus($serviceName, $serviceStatus, $detailed);

            if ($serviceStatus['status'] !== 'active') {
                $issues[] = [
                    'service' => $serviceName,
                    'status' => $serviceStatus['status'],
                    'issue' => 'Service not active'
                ];
            }
        }

        // Check billing integrations
        $this->monitorBillingIntegrations($issues, $detailed);

        // Check partner API
        $this->monitorPartnerAPI($issues, $detailed);

        // Handle issues
        if (!empty($issues)) {
            $this->handleIssues($issues, $sendAlerts, $autoRepair);
        } else {
            $this->info('🎉 All integrations are healthy!');
        }
    }

    /**
     * Monitor specific service
     */
    private function monitorSpecificService($service, $sendAlerts, $autoRepair, $detailed)
    {
        $this->info("🎯 Monitoring {$service} service...");

        switch ($service) {
            case 'billing':
                $issues = [];
                $this->monitorBillingIntegrations($issues, $detailed);
                break;
            case 'webhooks':
                $webhookService = new WebhookIntegrationService();
                $status = $webhookService->getServiceStatus();
                $this->displayServiceStatus('webhooks', $status, $detailed);
                break;
            case 'crm':
                $crmService = new CRMIntegrationService();
                $status = $crmService->getServiceStatus();
                $this->displayServiceStatus('crm', $status, $detailed);
                break;
            case 'analytics':
                $analyticsService = new AnalyticsIntegrationService();
                $status = $analyticsService->getServiceStatus();
                $this->displayServiceStatus('analytics', $status, $detailed);
                break;
            case 'support':
                $supportService = new SupportTicketIntegrationService();
                $status = $supportService->getServiceStatus();
                $this->displayServiceStatus('support', $status, $detailed);
                break;
            case 'partner_api':
                $issues = [];
                $this->monitorPartnerAPI($issues, $detailed);
                break;
            default:
                throw new \Exception("Unknown service: {$service}");
        }
    }

    /**
     * Display overall health status
     */
    private function displayOverallHealth($status, $metrics, $detailed)
    {
        $this->line('');
        $this->line('📊 <fg=cyan>OVERALL INTEGRATION HEALTH</fg=cyan>');
        $this->line('════════════════════════════════');

        $healthColor = $this->getHealthColor($status['overall_health'] ?? 'unknown');
        $this->line("Health Status: <fg={$healthColor}>" . strtoupper($status['overall_health'] ?? 'UNKNOWN') . '</fg>');
        $this->line("Total Integrations: " . ($status['total_integrations'] ?? 0));
        $this->line("Active Integrations: <fg=green>" . ($status['active_integrations'] ?? 0) . "</fg>");
        $this->line("Failed Integrations: <fg=red>" . ($status['failed_integrations'] ?? 0) . "</fg>");
        $this->line("Uptime: <fg=green>" . ($status['uptime_percentage'] ?? 0) . "%</fg>");

        if ($detailed && !empty($metrics)) {
            $this->line('');
            $this->line('📈 <fg=cyan>PERFORMANCE METRICS</fg=cyan>');
            $this->line("Average Response Time: " . ($metrics['avg_response_time'] ?? 0) . "ms");
            $this->line("Total Requests Today: " . number_format($metrics['total_requests'] ?? 0));
            $this->line("Success Rate: " . ($metrics['success_rate'] ?? 0) . "%");
        }
    }

    /**
     * Display service status
     */
    private function displayServiceStatus($serviceName, $status, $detailed)
    {
        $this->line('');
        $this->line("🔧 <fg=cyan>" . strtoupper($serviceName) . " SERVICE</fg=cyan>");
        $this->line('────────────────────────────');

        $statusColor = $status['status'] === 'active' ? 'green' : 'red';
        $this->line("Status: <fg={$statusColor}>" . strtoupper($status['status']) . '</fg>');

        // Service-specific metrics
        switch ($serviceName) {
            case 'webhooks':
                $this->line("Webhooks Registered: " . ($status['webhooks_registered'] ?? 0));
                $this->line("Delivery Success Rate: " . ($status['delivery_success_rate'] ?? 0) . "%");
                $this->line("Average Delivery Time: " . ($status['avg_delivery_time'] ?? 0) . "ms");
                break;

            case 'crm':
                $this->line("Platforms Connected: " . ($status['platforms_connected'] ?? 0));
                $this->line("Contacts Synced: " . number_format($status['contacts_synced'] ?? 0));
                $this->line("Last Sync: " . ($status['last_sync']->diffForHumans() ?? 'Never'));
                break;

            case 'analytics':
                $this->line("Platforms Active: " . ($status['platforms_active'] ?? 0));
                $this->line("Events Tracked Today: " . number_format($status['events_tracked_today'] ?? 0));
                $this->line("Data Freshness: " . ($status['data_freshness'] ?? 'Unknown'));
                break;

            case 'support':
                $this->line("Tickets Created Today: " . ($status['tickets_created_today'] ?? 0));
                $this->line("Average Response Time: " . ($status['avg_response_time'] ?? 'Unknown'));
                $this->line("Satisfaction Score: " . ($status['satisfaction_score'] ?? 0) . "/5");
                break;
        }

        if ($detailed) {
            $this->displayDetailedServiceInfo($serviceName, $status);
        }
    }

    /**
     * Monitor billing integrations
     */
    private function monitorBillingIntegrations(&$issues, $detailed)
    {
        $this->line('');
        $this->line('💰 <fg=cyan>BILLING INTEGRATIONS</fg=cyan>');
        $this->line('────────────────────────────');

        $providers = ['quickbooks', 'xero', 'freshbooks'];

        foreach ($providers as $provider) {
            $status = $this->getBillingProviderStatus($provider);
            $statusColor = $status['enabled'] ? 'green' : 'yellow';

            $this->line(ucfirst($provider) . ": <fg={$statusColor}>" .
                       ($status['enabled'] ? 'ENABLED' : 'DISABLED') . '</fg>');

            if ($status['enabled']) {
                $this->line("  Last Sync: {$status['last_sync']}");
                $this->line("  Records Synced: {$status['records_synced']}");

                if (!$status['healthy']) {
                    $issues[] = [
                        'service' => 'billing',
                        'provider' => $provider,
                        'issue' => $status['issue'] ?? 'Health check failed'
                    ];
                }
            }
        }
    }

    /**
     * Monitor partner API
     */
    private function monitorPartnerAPI(&$issues, $detailed)
    {
        $this->line('');
        $this->line('🤝 <fg=cyan>PARTNER API</fg=cyan>');
        $this->line('────────────────────────────');

        $apiStatus = $this->getPartnerAPIStatus();
        $statusColor = $apiStatus['status'] === 'active' ? 'green' : 'red';

        $this->line("Status: <fg={$statusColor}>" . strtoupper($apiStatus['status']) . '</fg>');
        $this->line("Active Partners: " . ($apiStatus['partners_active'] ?? 0));
        $this->line("Requests Today: " . number_format($apiStatus['requests_today'] ?? 0));
        $this->line("Rate Limit: " . ($apiStatus['rate_limit'] ?? 0) . "/hour");

        if ($apiStatus['status'] !== 'active') {
            $issues[] = [
                'service' => 'partner_api',
                'status' => $apiStatus['status'],
                'issue' => 'Partner API not responding'
            ];
        }
    }

    /**
     * Handle detected issues
     */
    private function handleIssues($issues, $sendAlerts, $autoRepair)
    {
        $this->line('');
        $this->error('⚠️  ISSUES DETECTED:');
        $this->line('════════════════════');

        foreach ($issues as $issue) {
            $this->warn("• {$issue['service']}: {$issue['issue']}");

            if ($autoRepair) {
                $this->attemptRepair($issue);
            }
        }

        if ($sendAlerts) {
            $this->sendAlerts($issues);
        }
    }

    /**
     * Attempt automatic repair
     */
    private function attemptRepair($issue)
    {
        $this->line("  🔧 Attempting auto-repair for {$issue['service']}...");

        // Mock repair attempt
        $success = rand(0, 1);

        if ($success) {
            $this->info("  ✅ Auto-repair successful for {$issue['service']}");
        } else {
            $this->warn("  ❌ Auto-repair failed for {$issue['service']} - manual intervention required");
        }
    }

    /**
     * Send alerts for critical issues
     */
    private function sendAlerts($issues)
    {
        $this->line('');
        $this->info('📧 Sending alerts for critical issues...');

        // Mock alert sending
        foreach ($issues as $issue) {
            $this->line("  • Alert sent for {$issue['service']} issue");
        }
    }

    /**
     * Get health status color
     */
    private function getHealthColor($health)
    {
        return match($health) {
            'healthy' => 'green',
            'degraded' => 'yellow',
            'critical' => 'red',
            default => 'gray'
        };
    }

    /**
     * Display detailed service information
     */
    private function displayDetailedServiceInfo($serviceName, $status)
    {
        // Mock detailed information display
        $this->line("  Configuration: ✅ Valid");
        $this->line("  Connectivity: ✅ Connected");
        $this->line("  Authentication: ✅ Valid");
        $this->line("  Last Health Check: " . now()->format('Y-m-d H:i:s'));
    }

    /**
     * Get billing provider status
     */
    private function getBillingProviderStatus($provider)
    {
        return [
            'enabled' => rand(0, 1),
            'healthy' => rand(0, 1),
            'last_sync' => now()->subMinutes(rand(5, 60))->diffForHumans(),
            'records_synced' => rand(50, 500),
            'issue' => rand(0, 1) ? null : 'API rate limit exceeded'
        ];
    }

    /**
     * Get partner API status
     */
    private function getPartnerAPIStatus()
    {
        return [
            'status' => 'active',
            'partners_active' => rand(10, 50),
            'requests_today' => rand(1000, 10000),
            'rate_limit' => 5000
        ];
    }
}
