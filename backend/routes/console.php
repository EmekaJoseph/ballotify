<?php

use Illuminate\Support\Facades\Schedule;

// Housekeeping. Run `php artisan schedule:work` in development, or add one cron
// entry in production: * * * * * cd /path/to/backend && php artisan schedule:run
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('ballotify:purge-voter-contacts')->dailyAt('03:00');
