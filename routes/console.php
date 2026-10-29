<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('dms:process-reminders')->dailyAt('08:00')->withoutOverlapping();
