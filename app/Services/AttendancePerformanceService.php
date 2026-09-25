<?php

namespace App\Services;

use App\Models\AttendanceRecap;
use App\Models\Employee;
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
     * Untuk tahun berjalan: bulan yang belum berjalan = 0, bulan berjalan dihitung
     * sampai hari ini saja (bukan sebulan penuh).
     */
    protected function workdaysInMonth(int $year, int $month): int
    {
        $start = Carbon::create($year, $month, 1);
        $today = Carbon::today();

        // Bulan yang belum berjalan (di tahun berjalan / masa depan) tidak dihitung.
        if ($start->gt($today)) {
            return 0;
        }

        // Bulan berjalan: batasi sampai hari ini.
        $lastDay = $start->isSameMonth($today) ? $today->day : $start->daysInMonth;

        $workdays = 0;

        for ($d = 1; $d <= $lastDay; $d++) {
            $date = Carbon::create($year, $month, $d);
            if (! $date->isWeekend()) {
                $workdays++;
            }
        }

        $holidays = Holiday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->filter(fn (Holiday $h) => ! $h->date->isWeekend() && $h->date->day <= $lastDay)
            ->count();

        return max(0, $workdays - $holidays);
    }

    /**
     * Data kalender kehadiran 1 pegawai untuk 1 bulan (dipakai halaman detail pegawai).
     *
     * @return array<int, array{day:int, date:Carbon, weekday:string, is_today:bool, status:array{c:string,label:string,title:string}}>
     */
    public function calendarForEmployee(int $employeeId, Carbon $month): array
    {
        $recaps = AttendanceRecap::where('employee_id', $employeeId)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->get()
            ->keyBy(fn (AttendanceRecap $r) => (int) $r->date->format('d'));

        $holidays = Holiday::whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->get()
            ->keyBy(fn (Holiday $h) => (int) $h->date->format('d'));

        $today = Carbon::today();

        $cellMap = [
            'hadir' => ['c' => 'c-hadir', 'label' => 'H'],
            'telat' => ['c' => 'c-telat', 'label' => 'T'],
            'pulang_cepat' => ['c' => 'c-pulang', 'label' => 'P'],
            'izin' => ['c' => 'c-izin', 'label' => 'I'],
            'sakit' => ['c' => 'c-sakit', 'label' => 'S'],
            'cuti' => ['c' => 'c-cuti', 'label' => 'C'],
            'dinas_luar' => ['c' => 'c-dinas', 'label' => 'D'],
        ];

        $days = [];

        for ($d = 1; $d <= $month->daysInMonth; $d++) {
            $date = Carbon::create($month->year, $month->month, $d);
            $recap = $recaps->get($d);
            $holiday = $holidays->get($d);

            if ($date->isWeekend()) {
                $status = ['c' => 'c-weekend', 'label' => '•', 'title' => 'Akhir Pekan'];
            } elseif ($holiday) {
                $status = ['c' => 'c-libur', 'label' => 'L', 'title' => 'Libur: '.$holiday->name];
            } elseif ($date->gt($today)) {
                $status = ['c' => 'c-null', 'label' => '·', 'title' => ''];
            } elseif (! $recap) {
                $status = ['c' => 'c-alpa', 'label' => 'A', 'title' => 'Alpa'];
            } else {
                $st = $recap->status();
                $cell = $cellMap[$st['key']] ?? ['c' => 'c-hadir', 'label' => 'H'];
                $status = ['c' => $cell['c'], 'label' => $cell['label'], 'title' => $st['label']];
            }

            $days[] = [
                'day' => $d,
                'date' => $date,
                'weekday' => $date->translatedFormat('D'),
                'is_today' => $date->isSameDay($today),
                'status' => $status,
            ];
        }

        return $days;
    }

    /**
     * Agregat 1 bulan per pegawai (untuk tabel ringkasan Rekap Bulanan).
     */
    public function monthlyByEmployee(int $year, int $month): Collection
    {
        return AttendanceRecap::query()
            ->selectRaw('employee_id, COUNT(*) as hadir, SUM(late_minutes > 0) as telat, SUM(late_minutes) as menit_telat, SUM(early_leave_minutes > 0) as pulang')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->groupBy('employee_id')
            ->with('employee')
            ->get()
            ->sortBy([['telat', 'asc'], ['menit_telat', 'asc']])
            ->values();
    }

    /**
     * Matriks pegawai × tanggal (hari kerja) untuk 1 bulan.
     *
     * @return array{employees: Collection, days: array<int, Carbon>, cells: array<string, array{c:string,label:string,title:string}>}
     */
    public function matrixForMonth(Carbon $month): array
    {
        $days = [];
        for ($d = 1; $d <= $month->daysInMonth; $d++) {
            $date = Carbon::create($month->year, $month->month, $d);
            if (! $date->isWeekend()) {
                $days[] = $date;
            }
        }

        $recaps = AttendanceRecap::whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->get()
            ->keyBy(fn (AttendanceRecap $r) => $r->employee_id.'|'.$r->date->toDateString());

        $holidays = Holiday::whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->get()
            ->keyBy(fn (Holiday $h) => $h->date->toDateString());

        $employees = Employee::where('is_admin', false)->orderBy('name')->get();

        $cellMap = [
            'hadir' => ['c' => 'c-hadir', 'label' => 'H', 'title' => 'Hadir'],
            'telat' => ['c' => 'c-telat', 'label' => 'T', 'title' => 'Telat'],
            'pulang_cepat' => ['c' => 'c-pulang', 'label' => 'P', 'title' => 'Pulang Cepat'],
            'izin' => ['c' => 'c-izin', 'label' => 'I', 'title' => 'Izin'],
            'sakit' => ['c' => 'c-sakit', 'label' => 'S', 'title' => 'Sakit'],
            'cuti' => ['c' => 'c-cuti', 'label' => 'C', 'title' => 'Cuti'],
            'dinas_luar' => ['c' => 'c-dinas', 'label' => 'D', 'title' => 'Dinas Luar'],
        ];

        $today = Carbon::today();
        $cells = [];

        foreach ($employees as $employee) {
            foreach ($days as $date) {
                $key = $employee->id.'|'.$date->toDateString();
                $recap = $recaps->get($key);
                $holiday = $holidays->get($date->toDateString());

                if ($holiday) {
                    $cells[$key] = ['c' => 'c-libur', 'label' => 'L', 'title' => 'Libur: '.$holiday->name];
                } elseif ($date->gt($today)) {
                    $cells[$key] = ['c' => 'c-null', 'label' => '·', 'title' => ''];
                } elseif (! $recap) {
                    $cells[$key] = ['c' => 'c-alpa', 'label' => 'A', 'title' => 'Alpa'];
                } else {
                    $st = $recap->status();
                    $cells[$key] = $cellMap[$st['key']] ?? ['c' => 'c-hadir', 'label' => 'H', 'title' => 'Hadir'];
                }
            }
        }

        return compact('employees', 'days', 'cells');
    }

    /**
     * Ringkasan setahun dari data bulanan.
     *
     * @param  array<int, array{hadir:int, telat:int, pulang:int, menit_telat:int, hari_kerja:int}>  $monthly
     * @return array<string, float|int>
     */
    public function summarize(array $monthly): array
    {
        $hadir = array_sum(array_column($monthly, 'hadir'));
        $telat = array_sum(array_column($monthly, 'telat'));
        $pulang = array_sum(array_column($monthly, 'pulang'));
        $menit = array_sum(array_column($monthly, 'menit_telat'));
        $hariKerja = array_sum(array_column($monthly, 'hari_kerja'));

        return [
            'hadir' => $hadir,
            'telat' => $telat,
            'pulang' => $pulang,
            'menit_telat' => $menit,
            'hari_kerja' => $hariKerja,
            'persen_hadir' => $hariKerja > 0 ? round($hadir / $hariKerja * 100, 1) : 0,
            'persen_tepat' => $hadir > 0 ? round(($hadir - $telat) / $hadir * 100, 1) : 0,
        ];
    }
}
