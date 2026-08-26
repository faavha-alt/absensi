<?php

namespace App\Listeners;

use App\Models\Employee;
use App\Services\AttendanceRecapCalculator;
use Illuminate\Support\Carbon;
use Syofyanzuhad\FilamentZktecoAdms\Events\AttendanceReceived;

class RecomputeAttendanceRecap
{
    public function __construct(protected AttendanceRecapCalculator $calculator) {}

    public function handle(AttendanceReceived $event): void
    {
        $employee = Employee::where('zkteco_pin', $event->attendanceLog->pin)->first();

        if (! $employee) {
            return;
        }

        $this->calculator->recomputeForEmployeeAndDate(
            $employee,
            Carbon::parse($event->attendanceLog->punched_at)
        );
    }
}
