<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every day at 00:05 (Pakistan time): create pending follow-up rows for patients who are due today
Schedule::command('followups:create-pending')
    ->dailyAt('00:05')
    ->withoutOverlapping();