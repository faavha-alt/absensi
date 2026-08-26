<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rekonsiliasi rekap absensi harian, jaga-jaga kalau ada event yang terlewat
// (device offline lalu push backlog, restart server, dsb).
Schedule::command('attendance:recompute-recaps')
    ->dailyAt('04:00')
    ->onOneServer()
    ->withoutOverlapping();
