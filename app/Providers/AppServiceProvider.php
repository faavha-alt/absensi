<?php

namespace App\Providers;

use App\Listeners\RecomputeAttendanceRecap;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Syofyanzuhad\FilamentZktecoAdms\Events\AttendanceReceived;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(AttendanceReceived::class, RecomputeAttendanceRecap::class);
    }
}
