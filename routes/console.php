<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('queue:work --tries=3 --delay=60 --timeout=600 --stop-when-empty')
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue.log'));

Schedule::command('sites:dispatch-checks')
    ->dailyAt('02:00')
    ->timezone('Asia/Dhaka')
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue.log'));
