<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Housekeeping. Run `php artisan schedule:work` in development, or add one cron
// entry in production: * * * * * cd /path/to/backend && php artisan schedule:run
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('ballotify:purge-voter-contacts')->dailyAt('03:00');
Schedule::command('ballotify:send-reminders')->everyMinute()->withoutOverlapping();

// Lets the admin health panel tell whether the scheduler is actually running.
Schedule::call(fn () => Cache::forever('ballotify.scheduler_heartbeat', now()->toIso8601String()))
    ->everyMinute()
    ->name('scheduler-heartbeat');
