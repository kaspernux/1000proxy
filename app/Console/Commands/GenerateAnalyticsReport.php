<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdvancedAnalyticsService;

class GenerateAnalyticsReport extends Command
{
    protected $signature = 'analytics:generate-report {--period=daily : Report period (daily, weekly, monthly)}';
    protected $description = 'Generate comprehensive analytics report';
    
    public function handle(AdvancedAnalyticsService $analyticsService)
    {
        $period = $this->option('period');
        $this->info("Generating $period analytics report...");
        
        try {
            // Get business metrics
            $metrics = $analyticsService->getBusinessMetrics();
            
            $this->info('Business Metrics:');
            $this->info('  Revenue: $' . number_format($metrics['revenue']['total'] ?? 0, 2));
            $this->info('  Orders: ' . number_format($metrics['orders']['total'] ?? 0));
            $this->info('  Active Users: ' . number_format($metrics['users']['active'] ?? 0));
            $this->info('  Conversion Rate: ' . number_format($metrics['conversion_rate'] ?? 0, 2) . '%');
            
            // Get performance metrics
            $performance = $analyticsService->getPerformanceMetrics();
            
            $this->info('Performance Metrics:');
            $this->info('  Avg Response Time: ' . ($performance['avg_response_time'] ?? 'N/A') . 'ms');
            $this->info('  Error Rate: ' . number_format($performance['error_rate'] ?? 0, 2) . '%');
            $this->info('  Uptime: ' . number_format($performance['uptime'] ?? 0, 2) . '%');
            
            // Get server metrics
            $servers = $analyticsService->getServerMetrics();
            
            $this->info('Server Metrics:');
            $this->info('  Active Servers: ' . ($servers['active_count'] ?? 0));
            $this->info('  Avg Load: ' . number_format($servers['avg_load'] ?? 0, 2) . '%');
            $this->info('  Top Performing: ' . ($servers['top_server'] ?? 'N/A'));
            
            // Get forecasting data
            $forecast = $analyticsService->getForecastData('revenue', $period);
            
            if (!empty($forecast)) {
                $this->info('Revenue Forecast:');
                foreach ($forecast as $period => $prediction) {
                    $this->info("  $period: $" . number_format($prediction['predicted_value'] ?? 0, 2));
                }
            }
            
            $this->info('Analytics report generated successfully.');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to generate analytics report: ' . $e->getMessage());
            return 1;
        }
    }
}
