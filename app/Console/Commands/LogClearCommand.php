<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:clear {--days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old log files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $logsPath = storage_path('logs');
        
        $this->info("Clearing log files older than {$days} days...");
        
        $files = File::files($logsPath);
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $fileAge = now()->diffInDays($file->getCTime());
            
            if ($fileAge > $days) {
                File::delete($file->getPathname());
                $deletedCount++;
                $this->line("Deleted: {$file->getFilename()}");
            }
        }
        
        $this->info("Cleared {$deletedCount} old log files.");
        
        return 0;
    }
}
