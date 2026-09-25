<?php

namespace App\Services;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\WorkSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Syofyanzuhad\FilamentZktecoAdms\Models\AttendanceLog;

class AttendanceRecapCalculator
{
    /**
     * Penalti pulang cepat (menit) yang diterapkan kalau tidak ada scan pulang yang valid
     * (baik karena cuma 1x scan, atau scan terakhir jatuh di luar jendela pulang yang wajar).
     * Dibatasi maksimal segini supaya tidak dianggap 'pulang sangat cepat' tanpa batas.
     */
    protected const NO_CHECKOUT_EARLY_LEAVE_MINUTES = 60;

    /**
     * Jam minimal (24h) supaya scan terakhir bisa dianggap scan pulang yang valid.
     * Scan sebelum jam ini (mis. scan ulang pagi) tidak dianggap check-out.
     */
    protected const CHECKOUT_MIN_HOUR = 12;

    /**
     * Alternatif: scan terakhir tetap dianggap check-out valid kalau berjarak minimal
     * sekian jam dari check-in, meskipun masih sebelum CHECKOUT_MIN_HOUR (mis. shift pagi pendek).
     */
    protected const CHECKOUT_MIN_HOURS_AFTER_CHECKIN = 4;

    public function recomputeForEmployeeAndDate(Employee $employee, Carbon $date): void
    {
        if ($employee->is_admin || $date->isWeekend() || $this->isHoliday($date)) {
            AttendanceRecap::where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->delete();

            return;
        }

        // Izin/sakit/cuti/dinas luar (dicatat manual admin) -> menang atas scan.
        $leaveType = Leave::typeForDate($employee->id, $date);

        if ($leaveType) {
            AttendanceRecap::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                [
                    'check_in' => null,
                    'check_out' => null,
                    'late_minutes' => 0,
                    'early_leave_minutes' => 0,
                    'leave_type' => $leaveType,
                ]
            );

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
        $checkOut = $this->resolveValidCheckOut($logs, $checkIn);

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
            // Tidak ada scan pulang yang valid (1x scan, atau scan terakhir di luar jendela pulang wajar)
            // -> anggap pulang cepat, dibatasi maksimal 1 jam.
            $earlyLeaveMinutes = self::NO_CHECKOUT_EARLY_LEAVE_MINUTES;
        }

        AttendanceRecap::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date->toDateString()],
            [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'late_minutes' => $lateMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'leave_type' => null,
            ]
        );
    }

    /**
     * @param  Collection<int, AttendanceLog>  $logs
     */
    protected function isHoliday(Carbon $date): bool
    {
        return Holiday::whereDate('date', $date)->exists();
    }

    protected function resolveValidCheckOut($logs, Carbon $checkIn): ?Carbon
    {
        if ($logs->count() <= 1) {
            return null;
        }

        $lastScan = $logs->last()->punched_at;

        $isAfternoon = $lastScan->hour >= self::CHECKOUT_MIN_HOUR;
        $isFarEnoughFromCheckIn = $checkIn->diffInHours($lastScan) >= self::CHECKOUT_MIN_HOURS_AFTER_CHECKIN;

        if ($isAfternoon || $isFarEnoughFromCheckIn) {
            return $lastScan;
        }

        return null;
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
