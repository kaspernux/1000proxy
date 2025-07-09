<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvancedAnalyticsService;

class GenerateAnalyticsReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:generate-report {--period=daily}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate analytics reports';

    /**
     * Execute the console command.
     */
    public function handle(AdvancedAnalyticsService $analyticsService)
    {
        $period = $this->option('period');
        
        $this->info("Generating {$period} analytics report...");
        
        $report = $analyticsService->generateReport($period);
        
        $this->info("Analytics report generated successfully:");
        $this->info("Period: {$period}");
        $this->info("Report data: " . json_encode($report, JSON_PRETTY_PRINT));
        
        return 0;
    }
}
