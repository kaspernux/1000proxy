<?php

namespace App\Services;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Jobs\ProcessXuiOrder;
use Carbon\Carbon;

class QueueOptimizationService
{
    // Queue names for different types of jobs
    const HIGH_PRIORITY_QUEUE = 'high';
    const DEFAULT_QUEUE = 'default';
    const LOW_PRIORITY_QUEUE = 'low';
    const BACKGROUND_QUEUE = 'background';
    const ANALYTICS_QUEUE = 'analytics';
    const NOTIFICATIONS_QUEUE = 'notifications';
    
    /**
     * Dispatch job to appropriate queue based on priority
     */
    public function dispatchJob($job, string $priority = 'default'): bool
    {
        try {
            $queueName = $this->getQueueName($priority);
            
            if ($job instanceof ProcessXuiOrder) {
                // High priority for order processing
                $job->onQueue(self::HIGH_PRIORITY_QUEUE);
            } else {
                $job->onQueue($queueName);
            }
            
            dispatch($job);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch job', [
                'job' => get_class($job),
                'priority' => $priority,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get queue name based on priority
     */
    private function getQueueName(string $priority): string
    {
        return match ($priority) {
            'high' => self::HIGH_PRIORITY_QUEUE,
            'low' => self::LOW_PRIORITY_QUEUE,
            'background' => self::BACKGROUND_QUEUE,
            'analytics' => self::ANALYTICS_QUEUE,
            'notifications' => self::NOTIFICATIONS_QUEUE,
            default => self::DEFAULT_QUEUE,
        };
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats(): array
    {
        try {
            $redis = Redis::connection();
            $stats = [];
            
            $queues = [
                self::HIGH_PRIORITY_QUEUE,
                self::DEFAULT_QUEUE,
                self::LOW_PRIORITY_QUEUE,
                self::BACKGROUND_QUEUE,
                self::ANALYTICS_QUEUE,
                self::NOTIFICATIONS_QUEUE
            ];
            
            foreach ($queues as $queue) {
                $queueKey = 'queues:' . $queue;
                $stats[$queue] = [
                    'size' => $redis->llen($queueKey),
                    'name' => $queue,
                    'workers' => $this->getActiveWorkers($queue),
                ];
            }
            
            // Get failed jobs count
            $stats['failed'] = [
                'count' => $redis->llen('queues:failed'),
                'name' => 'failed'
            ];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get queue stats', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get active workers for a queue
     */
    private function getActiveWorkers(string $queue): int
    {
        try {
            $redis = Redis::connection();
            $workers = $redis->smembers('queues:' . $queue . ':workers');
            
            $activeWorkers = 0;
            foreach ($workers as $worker) {
                if ($redis->exists('worker:' . $worker)) {
                    $activeWorkers++;
                }
            }
            
            return $activeWorkers;
        } catch (\Exception $e) {
            Log::error('Failed to get active workers', [
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Monitor queue health
     */
    public function monitorQueueHealth(): array
    {
        try {
            $stats = $this->getQueueStats();
            $health = [];
            
            foreach ($stats as $queueName => $queueStats) {
                if ($queueName === 'failed') {
                    continue;
                }
                
                $size = $queueStats['size'] ?? 0;
                $workers = $queueStats['workers'] ?? 0;
                
                $health[$queueName] = [
                    'status' => $this->getQueueHealthStatus($size, $workers),
                    'size' => $size,
                    'workers' => $workers,
                    'recommendations' => $this->getQueueRecommendations($queueName, $size, $workers)
                ];
            }
            
            // Check failed jobs
            $failedCount = $stats['failed']['count'] ?? 0;
            $health['failed'] = [
                'status' => $failedCount > 100 ? 'critical' : ($failedCount > 50 ? 'warning' : 'healthy'),
                'count' => $failedCount,
                'recommendations' => $failedCount > 50 ? ['Review and retry failed jobs'] : []
            ];
            
            return $health;
        } catch (\Exception $e) {
            Log::error('Failed to monitor queue health', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get queue health status
     */
    private function getQueueHealthStatus(int $size, int $workers): string
    {
        if ($size > 1000) {
            return 'critical';
        } elseif ($size > 500) {
            return 'warning';
        } elseif ($workers === 0 && $size > 0) {
            return 'warning';
        } else {
            return 'healthy';
        }
    }
    
    /**
     * Get queue recommendations
     */
    private function getQueueRecommendations(string $queueName, int $size, int $workers): array
    {
        $recommendations = [];
        
        if ($size > 1000) {
            $recommendations[] = "Queue $queueName has high backlog ($size jobs). Consider adding more workers.";
        }
        
        if ($workers === 0 && $size > 0) {
            $recommendations[] = "Queue $queueName has no active workers but has $size pending jobs.";
        }
        
        if ($queueName === self::HIGH_PRIORITY_QUEUE && $size > 100) {
            $recommendations[] = "High priority queue has significant backlog. This may affect customer experience.";
        }
        
        return $recommendations;
    }
    
    /**
     * Clear failed jobs older than specified days
     */
    public function clearOldFailedJobs(int $daysOld = 7): int
    {
        try {
            $redis = Redis::connection();
            $failedJobs = $redis->lrange('queues:failed', 0, -1);
            $cleared = 0;
            
            $cutoffTime = Carbon::now()->subDays($daysOld);
            
            foreach ($failedJobs as $index => $jobData) {
                $job = json_decode($jobData, true);
                
                if (isset($job['failed_at'])) {
                    $failedAt = Carbon::parse($job['failed_at']);
                    
                    if ($failedAt->lt($cutoffTime)) {
                        $redis->lrem('queues:failed', 1, $jobData);
                        $cleared++;
                    }
                }
            }
            
            Log::info('Cleared old failed jobs', ['cleared_count' => $cleared]);
            return $cleared;
        } catch (\Exception $e) {
            Log::error('Failed to clear old failed jobs', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Retry failed jobs
     */
    public function retryFailedJobs(int $limit = 10): int
    {
        try {
            $redis = Redis::connection();
            $failedJobs = $redis->lrange('queues:failed', 0, $limit - 1);
            $retried = 0;
            
            foreach ($failedJobs as $jobData) {
                $job = json_decode($jobData, true);
                
                if (isset($job['displayName'])) {
                    // Move job back to appropriate queue
                    $queueName = $job['queue'] ?? self::DEFAULT_QUEUE;
                    $redis->lpush('queues:' . $queueName, $jobData);
                    
                    // Remove from failed queue
                    $redis->lrem('queues:failed', 1, $jobData);
                    $retried++;
                }
            }
            
            Log::info('Retried failed jobs', ['retried_count' => $retried]);
            return $retried;
        } catch (\Exception $e) {
            Log::error('Failed to retry failed jobs', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Scale workers based on queue load
     */
    public function autoScaleWorkers(): array
    {
        try {
            $stats = $this->getQueueStats();
            $recommendations = [];
            
            foreach ($stats as $queueName => $queueStats) {
                if ($queueName === 'failed') {
                    continue;
                }
                
                $size = $queueStats['size'] ?? 0;
                $workers = $queueStats['workers'] ?? 0;
                
                // Recommend scaling based on queue size
                if ($size > 500 && $workers < 5) {
                    $recommendations[] = [
                        'queue' => $queueName,
                        'action' => 'scale_up',
                        'current_workers' => $workers,
                        'recommended_workers' => min(10, $workers + 3),
                        'reason' => "High queue load ($size jobs)"
                    ];
                } elseif ($size < 50 && $workers > 2) {
                    $recommendations[] = [
                        'queue' => $queueName,
                        'action' => 'scale_down',
                        'current_workers' => $workers,
                        'recommended_workers' => max(1, $workers - 1),
                        'reason' => "Low queue load ($size jobs)"
                    ];
                }
            }
            
            return $recommendations;
        } catch (\Exception $e) {
            Log::error('Failed to auto-scale workers', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get queue performance metrics
     */
    public function getQueuePerformanceMetrics(): array
    {
        try {
            $redis = Redis::connection();
            $metrics = [];
            
            // Get processing times for completed jobs
            $completedJobs = $redis->lrange('queues:completed', 0, 99);
            $totalProcessingTime = 0;
            $jobCount = 0;
            
            foreach ($completedJobs as $jobData) {
                $job = json_decode($jobData, true);
                if (isset($job['processing_time'])) {
                    $totalProcessingTime += $job['processing_time'];
                    $jobCount++;
                }
            }
            
            $metrics['average_processing_time'] = $jobCount > 0 ? round($totalProcessingTime / $jobCount, 2) : 0;
            $metrics['completed_jobs_count'] = $jobCount;
            
            // Get queue throughput (jobs per minute)
            $recentJobs = $redis->lrange('queues:completed', 0, 999);
            $recentJobsCount = 0;
            $oneMinuteAgo = Carbon::now()->subMinute();
            
            foreach ($recentJobs as $jobData) {
                $job = json_decode($jobData, true);
                if (isset($job['completed_at'])) {
                    $completedAt = Carbon::parse($job['completed_at']);
                    if ($completedAt->gt($oneMinuteAgo)) {
                        $recentJobsCount++;
                    }
                }
            }
            
            $metrics['throughput_per_minute'] = $recentJobsCount;
            $metrics['timestamp'] = Carbon::now()->toISOString();
            
            return $metrics;
        } catch (\Exception $e) {
            Log::error('Failed to get queue performance metrics', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
