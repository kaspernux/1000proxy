<?php

namespace App\Console\Commands;

use App\Services\PartnershipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPartnershipData extends Command
{
    protected $signature = 'partnership:sync {service?}';
    protected $description = 'Sync data with partnership services';
    
    public function handle(PartnershipService $partnershipService): int
    {
        $service = $this->argument('service');
        
        if ($service) {
            $this->info("Syncing data with {$service}...");
            
            try {
                $success = $partnershipService->syncPartnershipData($service);
                
                if ($success) {
                    $this->info("Successfully synced data with {$service}");
                    return Command::SUCCESS;
                } else {
                    $this->error("Failed to sync data with {$service}");
                    return Command::FAILURE;
                }
                
            } catch (\Exception $e) {
                $this->error("Error syncing with {$service}: " . $e->getMessage());
                Log::error("Partnership sync error: " . $e->getMessage());
                return Command::FAILURE;
            }
        }
        
        // Sync with all available partnerships
        $this->info('Syncing data with all partnership services...');
        
        $partnerships = $partnershipService->getAvailablePartnerships();
        $successCount = 0;
        
        foreach ($partnerships as $name => $partnership) {
            if ($partnership['integration_status'] === 'active') {
                $this->line("Syncing with {$partnership['name']}...");
                
                try {
                    $success = $partnershipService->syncPartnershipData($name);
                    
                    if ($success) {
                        $this->info("✓ {$partnership['name']} sync completed");
                        $successCount++;
                    } else {
                        $this->error("✗ {$partnership['name']} sync failed");
                    }
                    
                } catch (\Exception $e) {
                    $this->error("✗ {$partnership['name']} sync error: " . $e->getMessage());
                    Log::error("Partnership sync error for {$name}: " . $e->getMessage());
                }
            }
        }
        
        $this->info("Partnership sync completed. {$successCount} services synchronized successfully.");
        
        return Command::SUCCESS;
    }
}
