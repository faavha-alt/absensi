<?php

namespace App\Services;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Illuminate\Support\Carbon;
use Syofyanzuhad\FilamentZktecoAdms\Models\AttendanceLog;

class AttendanceRecapCalculator
{
    /**
     * Penalti pulang cepat (menit) yang diterapkan kalau pegawai tidak scan pulang sama sekali.
     * Dibatasi maksimal segini supaya tidak dianggap 'pulang sangat cepat' tanpa batas.
     */
    protected const NO_CHECKOUT_EARLY_LEAVE_MINUTES = 60;

    public function recomputeForEmployeeAndDate(Employee $employee, Carbon $date): void
    {
        if ($employee->is_admin || $date->isWeekend()) {
            AttendanceRecap::where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->delete();

            return;
        }

        $logs = AttendanceLog::where('pin', $employee->zkteco_pin)
            ->whereDate('punched_at', $date)
            ->orderBy('punched_at')
            ->get();

        if ($logs->isEmpty()) {
            AttendanceRecap::where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->delete();

            return;
        }

        $checkIn = $logs->first()->punched_at;
        $checkOut = $logs->count() > 1 ? $logs->last()->punched_at : null;

        $schedule = WorkSchedule::current();

        $lateThreshold = $date->copy()
            ->setTimeFromTimeString($schedule->time_in->format('H:i:s'))
            ->addMinutes($schedule->late_tolerance_minutes);

        $lateMinutes = $checkIn->gt($lateThreshold)
            ? abs($checkIn->diffInMinutes($lateThreshold))
            : 0;

        if ($checkOut) {
            $earlyLeaveThreshold = $date->copy()
                ->setTimeFromTimeString($schedule->time_out->format('H:i:s'))
                ->subMinutes($schedule->early_leave_tolerance_minutes);

            $earlyLeaveMinutes = $checkOut->lt($earlyLeaveThreshold)
                ? abs($earlyLeaveThreshold->diffInMinutes($checkOut))
                : 0;
        } else {
            // Tidak ada scan pulang sama sekali -> anggap pulang cepat, dibatasi maksimal 1 jam.
            $earlyLeaveMinutes = self::NO_CHECKOUT_EARLY_LEAVE_MINUTES;
        }

        AttendanceRecap::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date->toDateString()],
            [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'late_minutes' => $lateMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
            ]
        );
    }

    public function recomputeAll(): int
    {
        $count = 0;

        $employees = Employee::whereNotNull('zkteco_pin')->where('is_admin', false)->get();

        AttendanceRecap::whereIn('employee_id', Employee::where('is_admin', true)->pluck('id'))->delete();

        foreach ($employees as $employee) {
            $dates = AttendanceLog::where('pin', $employee->zkteco_pin)
                ->selectRaw('DATE(punched_at) as d')
                ->distinct()
                ->pluck('d');

            foreach ($dates as $d) {
                $this->recomputeForEmployeeAndDate($employee, Carbon::parse($d));
                $count++;
            }
        }

        return $count;
    }
}
