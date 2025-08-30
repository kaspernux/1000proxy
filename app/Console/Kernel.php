<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\XuiDiagnoseCommand::class,
        \App\Console\Commands\DispatchFeatureAdXuiFetch::class,
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Warm dashboard/server metrics every 5 minutes; force once hourly.
        $schedule->command('metrics:refresh')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('metrics:refresh --force')->hourly()->withoutOverlapping();
        // Dispatch feature ad X-UI fetch for active ads every 5 minutes
        $schedule->command('featuread:fetch-xui --only-active')->everyFiveMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
