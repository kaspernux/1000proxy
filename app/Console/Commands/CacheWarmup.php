<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheOptimizationService;

class CacheWarmup extends Command
{
    protected $signature = 'cache:warmup';
    protected $description = 'Warm up cache with critical data';
    
    public function handle(CacheOptimizationService $cacheService)
    {
        $this->info('Warming up cache...');
        
        $result = $cacheService->warmUpCache();
        
        if ($result) {
            $this->info('Cache warmed up successfully.');
            
            // Display cache stats
            $stats = $cacheService->getCacheStats();
            $this->info('Cache Statistics:');
            $this->info('  Hit Rate: ' . ($stats['hit_rate'] ?? 'N/A'));
            $this->info('  Memory Usage: ' . ($stats['memory_usage'] ?? 'N/A'));
            $this->info('  Connected Clients: ' . ($stats['connected_clients'] ?? 'N/A'));
            
            return 0;
        } else {
            $this->error('Failed to warm up cache.');
            return 1;
        }
    }
}
