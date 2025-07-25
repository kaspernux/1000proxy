<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonitoringService;
use App\Services\CacheOptimizationService;
use App\Services\QueueOptimizationService;

class SystemHealthCheck extends Command
{
    protected $signature = 'system:health-check';
    protected $description = 'Run comprehensive system health check';
    
    public function handle(
        MonitoringService $monitoringService,
        CacheOptimizationService $cacheService,
        QueueOptimizationService $queueService
    ) {
        $this->info('Running system health check...');
        
        $healthStatus = $monitoringService->runHealthCheck();
        
        $this->info('Health Check Results:');
        $this->info('Overall Status: ' . strtoupper($healthStatus['overall']));
        
        foreach ($healthStatus['checks'] as $checkName => $check) {
            $status = strtoupper($check['status']);
            $this->line("  $checkName: $status");
            
            if (!empty($check['issues'])) {
                foreach ($check['issues'] as $issue) {
                    $this->warn("    - $issue");
                }
            }
        }
        
        if ($healthStatus['overall'] === 'critical') {
            $this->error('System health is CRITICAL! Immediate attention required.');
            return 1;
        } elseif ($healthStatus['overall'] === 'warning') {
            $this->warn('System health has warnings. Review recommended.');
            return 0;
        }
        
        $this->info('System health is good.');
        return 0;
    }
}
