<?php

namespace App\Filament\Admin\Widgets;

use App\Models\FailedJob;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TelegramQueueHealth extends BaseWidget
{
    protected ?string $pollingInterval = '10s';
    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        $queue = 'telegram';
        $conn = config('queue.default');
        $driver = config("queue.connections.$conn.driver");

        $queued = 0;
        $processing = 0;
        if ($driver === 'database') {
            $table = config('queue.connections.database.table', 'jobs');
            $queueCol = 'queue';
            $queued = (int) DB::table($table)->where($queueCol, $queue)->count();
            // With DB driver there is no reserved table by default; show 0 for processing
            $processing = 0;
        } elseif ($driver === 'redis') {
            // Optional: if using Redis, counts could be fetched via Redis facade and list lengths
            try {
                $redisConn = config('queue.connections.redis.connection', 'queue');
                $redisQueue = config('queue.connections.redis.queue', 'default');
                $redisKey = "queues:$queue";
                $queued = (int) app('redis')->connection($redisConn)->llen($redisKey);
            } catch (\Throwable $e) { /* ignore */ }
        }

        $failedCount = (int) (FailedJob::query()->count());

        return [
            Stat::make('Telegram queued', (string) $queued)->description('jobs waiting')->color($queued > 0 ? 'warning' : 'success'),
            Stat::make('Processing', (string) $processing)->description('in-flight')->color('info'),
            Stat::make('Failed', (string) $failedCount)->description('all queues')->color($failedCount > 0 ? 'danger' : 'success'),
        ];
    }
}
