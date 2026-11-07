<?php

use Illuminate\Support\Facades\Schedule;

// The hosting cron calls `php artisan schedule:run` every minute.
Schedule::command('football:daily')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('football:daily')->dailyAt('23:30')->withoutOverlapping();
Schedule::command('football:prepare-tomorrow')->dailyAt('22:00')->timezone('Africa/Lagos')->withoutOverlapping();
