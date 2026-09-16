<?php

use Illuminate\Support\Facades\Schedule;

// Shared-hosting cron only needs to call `php artisan schedule:run` once per minute.
// The application handles the actual command schedule here.
Schedule::command('football:daily')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('football:daily')->dailyAt('23:30')->withoutOverlapping();
