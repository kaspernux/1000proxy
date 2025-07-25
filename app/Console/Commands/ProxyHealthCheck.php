<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProxyHealthMonitor;

/**
 * Proxy Health Check Command
 *
 * Executes comprehensive health checks for all monitored proxies.
 */
class ProxyHealthCheck extends Command
{
    protected $signature = 'proxy:health-check {--user=} {--detailed}';
    protected $description = 'Execute health checks for all monitored proxies';

    protected $proxyHealthMonitor;

    public function __construct(ProxyHealthMonitor $proxyHealthMonitor)
    {
        parent::__construct();
        $this->proxyHealthMonitor = $proxyHealthMonitor;
    }

    public function handle()
    {
        $this->info('Starting proxy health check...');

        try {
            if ($this->option('user')) {
                $this->info("Checking health for user: {$this->option('user')}");
                $result = $this->proxyHealthMonitor->getRealTimeHealthStatus($this->option('user'));
            } else {
                $this->info('Executing comprehensive health check for all proxies...');
                $result = $this->proxyHealthMonitor->executeHealthCheck();
            }

            if ($result['success']) {
                $this->info("Health check completed successfully!");

                if (isset($result['summary'])) {
                    $summary = $result['summary'];
                    $this->info("Total proxies checked: {$summary['total_checked']}");
                    $this->info("Healthy: {$summary['healthy']}");
                    $this->info("Warning: {$summary['warning']}");
                    $this->info("Unhealthy: {$summary['unhealthy']}");
                    $this->info("Critical: {$summary['critical']}");
                }

                if ($this->option('detailed') && isset($result['results'])) {
                    $this->displayDetailedResults($result['results']);
                }
            } else {
                $this->error("Health check failed: {$result['error']}");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Health check error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function displayDetailedResults($results)
    {
        $this->info("\nDetailed Health Results:");

        $tableData = [];
        foreach ($results as $result) {
            if (!isset($result['proxy_id'])) continue;

            $tableData[] = [
                $result['proxy_id'],
                $result['user_id'] ?? 'N/A',
                $result['status'],
                $result['health_score'] ?? 'N/A',
                isset($result['metrics']['response_time']['value']) ? $result['metrics']['response_time']['value'] . 'ms' : 'N/A',
                count($result['issues'] ?? [])
            ];
        }

        $this->table(
            ['Proxy ID', 'User ID', 'Status', 'Health Score', 'Response Time', 'Issues'],
            $tableData
        );

        // Show issues if any
        foreach ($results as $result) {
            if (!empty($result['issues'])) {
                $this->warn("\nIssues for Proxy {$result['proxy_id']}:");
                foreach ($result['issues'] as $issue) {
                    $this->line("  - {$issue['metric']}: {$issue['description']}");
                }
            }
        }
    }
}
