<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule monitoring and maintenance tasks
Schedule::command('system:health-check')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('cache:warmup')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('queue:maintenance')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();

Schedule::command('analytics:generate-report --period=daily')
    ->daily()
    ->at('06:00')
    ->withoutOverlapping();

Schedule::command('analytics:generate-report --period=weekly')
    ->weekly()
    ->sundays()
    ->at('07:00')
    ->withoutOverlapping();

Schedule::command('analytics:generate-report --period=monthly')
    ->monthly()
    ->at('08:00')
    ->withoutOverlapping();

// Clean up old logs
Schedule::command('log:clear')
    ->weekly()
    ->sundays()
    ->at('03:00');
