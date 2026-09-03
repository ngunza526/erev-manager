<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// SEC-23 : purge quotidienne des jetons Sanctum expires
// (necessite le cron `php artisan schedule:run`).
Schedule::command('sanctum:prune-expired --hours=24')->daily();
