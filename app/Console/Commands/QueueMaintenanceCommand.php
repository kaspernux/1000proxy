<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueueOptimizationService;

class QueueMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:maintenance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform queue maintenance tasks';

    /**
     * Execute the console command.
     */
    public function handle(QueueOptimizationService $queueService)
    {
        $this->info('Performing queue maintenance...');
        
        // Clear failed jobs older than 7 days
        $queueService->clearOldFailedJobs(7);
        
        // Optimize queue performance
        $queueService->optimizeQueuePerformance();
        
        $this->info('Queue maintenance completed successfully.');
        
        return 0;
    }
}
