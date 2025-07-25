<?php

namespace App\Console\Commands;

use App\Services\CustomerSuccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunCustomerSuccessAutomation extends Command
{
    protected $signature = 'automation:customer-success';
    protected $description = 'Run customer success automation workflows';
    
    public function handle(CustomerSuccessService $customerSuccessService): int
    {
        $this->info('Starting customer success automation...');
        
        try {
            $customerSuccessService->runAutomation();
            
            $this->info('Customer success automation completed successfully!');
            Log::info('Customer success automation completed via command');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Customer success automation failed: ' . $e->getMessage());
            Log::error('Customer success automation failed: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
