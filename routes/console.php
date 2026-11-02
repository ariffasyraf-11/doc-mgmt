<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// Example 1: Scheduling a direct closure to log a message
Schedule::call(function () {
    Log::info('This is an automated log from the Laravel scheduler.');
})->everyMinute();

Schedule::command('dms:process-reminders')->dailyAt('08:00')->withoutOverlapping();

//->appendOutputTo(storage_path('logs/dms-reminder.log'));;


// //logging the start of the scheduled task
// Schedule::command('dms:process-reminders')->dailyAt('08:00')->withoutOverlapping()

// ->before(function () {
//     Log::info('Starting scheduled DMS reminder processing.');
// });
