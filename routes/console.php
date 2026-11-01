<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('payments:send-invoices')->dailyAt('08:00');
Schedule::command('meetings:send-reminders staff-summary')->dailyAt('07:00');
Schedule::command('meetings:send-reminders day-before')->dailyAt('08:00');
Schedule::command('meetings:send-reminders hour-before')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
