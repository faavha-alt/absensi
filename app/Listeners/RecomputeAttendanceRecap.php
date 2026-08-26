<?php

namespace App\Listeners;

use App\Models\Employee;
use App\Services\AttendanceRecapCalculator;
use Illuminate\Support\Carbon;
use Syofyanzuhad\FilamentZktecoAdms\Events\AttendanceReceived;

class RecomputeAttendanceRecap
{
    /**
     * Coalesce: device bisa push banyak log sekaligus dalam satu request (mis. sinkronisasi
     * backlog). Tanpa ini, tiap log memicu recompute terpisah walau (pegawai, tanggal) sama,
     * memperlambat respons ke device. Cukup 1x recompute per pasangan per request.
     *
     * @var array<string, true>
     */
    protected static array $recomputedInThisRequest = [];

    public function __construct(protected AttendanceRecapCalculator $calculator) {}

    public function handle(AttendanceReceived $event): void
    {
        $employee = Employee::where('zkteco_pin', $event->attendanceLog->pin)->first();

        if (! $employee) {
            return;
        }

        $date = Carbon::parse($event->attendanceLog->punched_at)->startOfDay();
        $key = $employee->id.':'.$date->toDateString();

        if (isset(self::$recomputedInThisRequest[$key])) {
            return;
        }

        self::$recomputedInThisRequest[$key] = true;

        $this->calculator->recomputeForEmployeeAndDate($employee, $date);
    }
}
