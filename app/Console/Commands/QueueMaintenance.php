<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueueOptimizationService;

class QueueMaintenance extends Command
{
    protected $signature = 'queue:maintenance {--clear-failed=7 : Clear failed jobs older than specified days}';
    protected $description = 'Perform queue maintenance tasks';
    
    public function handle(QueueOptimizationService $queueService)
    {
        $this->info('Performing queue maintenance...');
        
        // Clear old failed jobs
        $daysOld = (int) $this->option('clear-failed');
        $cleared = $queueService->clearOldFailedJobs($daysOld);
        $this->info("Cleared $cleared old failed jobs (older than $daysOld days).");
        
        // Get queue statistics
        $stats = $queueService->getQueueStats();
        $this->info('Queue Statistics:');
        
        foreach ($stats as $queueName => $queueStats) {
            if ($queueName === 'failed') {
                $this->info("  $queueName: " . $queueStats['count'] . ' jobs');
            } else {
                $size = $queueStats['size'] ?? 0;
                $workers = $queueStats['workers'] ?? 0;
                $this->info("  $queueName: $size jobs, $workers workers");
            }
        }
        
        // Get auto-scaling recommendations
        $recommendations = $queueService->autoScaleWorkers();
        if (!empty($recommendations)) {
            $this->info('Auto-scaling recommendations:');
            foreach ($recommendations as $recommendation) {
                $queue = $recommendation['queue'];
                $action = $recommendation['action'];
                $current = $recommendation['current_workers'];
                $recommended = $recommendation['recommended_workers'];
                $reason = $recommendation['reason'];
                
                $this->warn("  $queue: $action from $current to $recommended workers ($reason)");
            }
        }
        
        return 0;
    }
}
