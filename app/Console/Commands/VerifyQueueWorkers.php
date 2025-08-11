<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class VerifyQueueWorkers extends Command
{
	protected $signature = 'queue:verify-workers {--queue= : Specific queue name} {--min=1 : Minimum workers expected}';
	protected $description = 'Verify that queue workers are running and processing jobs; emit diagnostics and exit non-zero if below expectation.';

	public function handle(): int
	{
		$queue = $this->option('queue');
		$min = (int) $this->option('min');

		$this->info('Verifying queue workers...');
		$horizon = class_exists(\Laravel\Horizon\Horizon::class);
		$activeWorkers = 0;

		if ($horizon) {
			try {
				$masters = \Laravel\Horizon\MasterSupervisorRepository::all();
				foreach ($masters as $master) {
					foreach ($master->supervisors as $supervisor) {
						foreach ($supervisor->processes as $process) {
							if ($queue && !in_array($queue, $process->options['queue'] ?? [])) {
								continue;
							}
							$activeWorkers += (int) ($process->processes ?? 1);
						}
					}
				}
			} catch (\Throwable $e) {
				$this->warn('Horizon inspection failed: ' . $e->getMessage());
			}
		} else {
			// Fallback simple ps grep (may be restricted in some environments)
			try {
				$output = shell_exec('ps -ef | grep "queue:work" | grep -v grep');
				$lines = array_filter(explode("\n", trim((string)$output)));
				foreach ($lines as $line) {
					if ($queue && !str_contains($line, $queue)) {
						continue;
					}
					$activeWorkers++;
				}
			} catch (\Throwable $e) {
				$this->warn('Process inspection failed: ' . $e->getMessage());
			}
		}

		$this->info("Detected active workers: {$activeWorkers}");
		if ($activeWorkers < $min) {
			$this->error("Minimum workers not met (expected >= {$min}).");
			return self::FAILURE;
		}

		$pendingJobs = DB::table('jobs')->count();
		$failedJobs = DB::table('failed_jobs')->count();
		$this->line("Pending jobs: {$pendingJobs}");
		$this->line("Failed jobs: {$failedJobs}");

		if ($failedJobs > 0) {
			$this->warn('There are failed jobs; investigate with: php artisan queue:failed');
		}

		$this->info('Queue workers verification passed.');
		return self::SUCCESS;
	}
}
