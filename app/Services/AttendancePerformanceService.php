<?php

namespace App\Services;

use App\Models\AttendanceRecap;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan performa kehadiran pegawai untuk laporan (grafik).
 * Semua angka diturunkan dari tabel attendance_recaps.
 */
class AttendancePerformanceService
{
    /**
     * Performa bulanan 1 pegawai untuk 1 tahun (12 bulan).
     *
     * @return array<int, array{hadir:int, telat:int, pulang:int, menit_telat:int, hari_kerja:int}>
     */
    public function monthlyForEmployee(int $employeeId, int $year): array
    {
        $rows = AttendanceRecap::where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->get();

        $months = $this->emptyMonths();

        foreach ($rows as $r) {
            $m = (int) $r->date->format('n');
            $months[$m]['hadir']++;
            if ($r->late_minutes > 0) {
                $months[$m]['telat']++;
            }
            if ($r->early_leave_minutes > 0) {
                $months[$m]['pulang']++;
            }
            $months[$m]['menit_telat'] += (int) $r->late_minutes;
        }

        foreach (array_keys($months) as $m) {
            $months[$m]['hari_kerja'] = $this->workdaysInMonth($year, $m);
        }

        return $months;
    }

    /**
     * Agregat bulanan semua pegawai (non-admin) untuk 1 tahun.
     *
     * @return array<int, array{hadir:int, telat:int, pulang:int, menit_telat:int}>
     */
    public function monthlyTotals(int $year): array
    {
        $rows = AttendanceRecap::query()
            ->join('employees', 'employees.id', '=', 'attendance_recaps.employee_id')
            ->where('employees.is_admin', false)
            ->whereYear('attendance_recaps.date', $year)
            ->selectRaw('MONTH(attendance_recaps.date) as m, COUNT(*) as hadir, SUM(attendance_recaps.late_minutes > 0) as telat, SUM(attendance_recaps.early_leave_minutes > 0) as pulang, SUM(attendance_recaps.late_minutes) as menit_telat')
            ->groupBy('m')
            ->get()
            ->keyBy('m');

        $months = $this->emptyMonths();

        for ($m = 1; $m <= 12; $m++) {
            $r = $rows->get($m);
            $months[$m] = [
                'hadir' => (int) ($r->hadir ?? 0),
                'telat' => (int) ($r->telat ?? 0),
                'pulang' => (int) ($r->pulang ?? 0),
                'menit_telat' => (int) ($r->menit_telat ?? 0),
            ];
        }

        return $months;
    }

    /**
     * Total setahun per pegawai (untuk peringkat/ranking).
     */
    public function yearlyByEmployee(int $year): Collection
    {
        return AttendanceRecap::query()
            ->selectRaw('employee_id, COUNT(*) as hadir, SUM(late_minutes > 0) as telat, SUM(early_leave_minutes > 0) as pulang, SUM(late_minutes) as menit_telat')
            ->whereYear('date', $year)
            ->groupBy('employee_id')
            ->with('employee')
            ->get()
            ->sortBy([['telat', 'asc'], ['menit_telat', 'asc']])
            ->values();
    }

    /**
     * Daftar tahun yang tersedia dari data rekap.
     *
     * @return array<int, int>
     */
    public function availableYears(): array
    {
        $min = AttendanceRecap::min('date');
        $start = $min ? Carbon::parse($min)->year : now()->year;
        $end = now()->year;

        return range($end, $start);
    }

    /**
     * @return array<int, array{hadir:int, telat:int, pulang:int, menit_telat:int, hari_kerja:int}>
     */
    protected function emptyMonths(): array
    {
        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = ['hadir' => 0, 'telat' => 0, 'pulang' => 0, 'menit_telat' => 0, 'hari_kerja' => 0];
        }

        return $months;
    }

    /**
     * Jumlah hari kerja (Senin–Jumat dikurangi libur) dalam 1 bulan.
     */
    protected function workdaysInMonth(int $year, int $month): int
    {
        $start = Carbon::create($year, $month, 1);
        $workdays = 0;

        for ($d = 1; $d <= $start->daysInMonth; $d++) {
            $date = Carbon::create($year, $month, $d);
            if (! $date->isWeekend()) {
                $workdays++;
            }
        }

        $holidays = Holiday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->filter(fn (Holiday $h) => ! $h->date->isWeekend())
            ->count();

        return max(0, $workdays - $holidays);
    }
}
