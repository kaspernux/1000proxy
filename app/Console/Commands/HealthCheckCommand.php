<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonitoringService;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive system health check';

    /**
     * Execute the console command.
     */
    public function handle(MonitoringService $monitoring)
    {
        $this->info('Running system health check...');
        
        $healthStatus = $monitoring->runHealthCheck();
        
        // Display results
        $this->info('System Health Status: ' . strtoupper($healthStatus['overall']));
        $this->info('Timestamp: ' . $healthStatus['timestamp']);
        
        if ($healthStatus['overall'] === 'critical') {
            $this->error('CRITICAL: System requires immediate attention!');
        } elseif ($healthStatus['overall'] === 'warning') {
            $this->warn('WARNING: System has some issues that need attention.');
        } else {
            $this->info('SUCCESS: System is healthy.');
        }
        
        // Display detailed check results
        foreach ($healthStatus['checks'] as $checkName => $check) {
            $status = strtoupper($check['status']);
            $this->line("  {$checkName}: {$status}");
            
            if (!empty($check['issues'])) {
                foreach ($check['issues'] as $issue) {
                    $this->line("    - {$issue}");
                }
            }
        }
        
        return $healthStatus['overall'] === 'healthy' ? 0 : 1;
    }
}