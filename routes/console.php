<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Hostinger / shared-hosting schedule
|--------------------------------------------------------------------------
|
| Hostinger cron should call `php artisan schedule:run` every minute.
| Shared hosting usually cannot keep a long-lived queue:work process,
| so we drain the database queue every minute instead.
|
*/

Schedule::command('queue:work database --stop-when-empty --max-time=50 --max-jobs=5 --tries=3 --timeout=120 --sleep=1')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->name('hostinger-queue-worker')
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

Schedule::command('email:sync-imap')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->name('imap-inbox-sync')
    ->appendOutputTo(storage_path('logs/imap-sync.log'));

Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('03:15')
    ->name('prune-failed-jobs');

Schedule::command('permission:cache-reset')
    ->dailyAt('03:30')
    ->name('reset-permission-cache');
