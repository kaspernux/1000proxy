<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\PruneOldExportsJob;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new PruneOldExportsJob())->dailyAt('02:15');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
