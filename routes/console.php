<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:expire-family-invitations')->daily();

Schedule::command('app:generate-medication-logs')->dailyAt('00:05');
Schedule::command('app:process-medication-reminders')->everyFifteenMinutes();
Schedule::command('app:send-vaccination-reminders')->dailyAt('08:00');
