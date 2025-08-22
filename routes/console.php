<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-sync tournaments every 5 minutes
Schedule::command('tournaments:sync --active-only')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
