<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SmartRetryQueue extends Command
{
    protected $signature = 'queue:smart-retry {--max=25 : Max jobs to retry}';
    protected $description = 'Retry transient failed jobs (deadlocks, model not found, connection issues)';

    public function handle(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            $this->warn('failed_jobs table not present');
            return Command::SUCCESS;
        }

        $failed = DB::table('failed_jobs')
            ->select('id','uuid','exception')
            ->orderByDesc('id')
            ->limit((int)$this->option('max'))
            ->get();

        $patterns = [
            'Deadlock found',
            'Lock wait timeout',
            'try again',
            'Connection refused',
            'Connection reset',
            'SQLSTATE\\[HY000\\]',
            'ModelNotFoundException',
        ];
        $regex = '/'.implode('|', $patterns).'/i';
        $count = 0;
        foreach ($failed as $job) {
            if (preg_match($regex, $job->exception)) {
                $this->line("Retrying job uuid={$job->uuid}");
                Artisan::call('queue:retry', [$job->uuid]);
                $count++;
            }
        }
        $this->info("Retried {$count} job(s)");
        return Command::SUCCESS;
    }
}
