<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\AttendancePerformanceService;
use App\Services\GenericExcelBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalitikController extends Controller
{
    /** @var array<int, string> */
    protected const MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** @var array<int, string> */
    protected const MONTH_FULL = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /* =====================================================================
     | Performa Pegawai
     ===================================================================== */

    public function performa(Request $request): View
    {
        $service = app(AttendancePerformanceService::class);

        $employeeId = (int) $request->query('pegawai', (int) Employee::where('is_admin', false)->orderBy('name')->value('id'));
        $year = (int) $request->query('tahun', now()->year);
        $monthly = $service->monthlyForEmployee($employeeId, $year);

        return view('analitik.performa', [
            'employees' => Employee::where('is_admin', false)->orderBy('name')->get(),
            'employee' => Employee::find($employeeId),
            'employeeId' => $employeeId,
            'year' => $year,
            'monthly' => $monthly,
            'summary' => $service->summarize($monthly),
            'availableYears' => $service->availableYears(),
            'monthLabels' => self::MONTH_LABELS,
        ]);
    }

    public function exportPerforma(Request $request): StreamedResponse
    {
        $service = app(AttendancePerformanceService::class);

        $employeeId = (int) $request->query('pegawai', 0);
        $year = (int) $request->query('tahun', now()->year);
        $employee = Employee::find($employeeId);
        $monthly = $service->monthlyForEmployee($employeeId, $year);

        $rows = [];
        foreach ($monthly as $m => $data) {
            $rows[] = [self::MONTH_FULL[$m] ?? $m, $data['hadir'], $data['telat'], $data['menit_telat'], $data['pulang'], $data['hari_kerja']];
        }

        $s = $service->summarize($monthly);
        $rows[] = ['TOTAL', $s['hadir'], $s['telat'], $s['menit_telat'], $s['pulang'], $s['hari_kerja']];

        $path = app(GenericExcelBuilder::class)->build(
            'PERFORMA '.strtoupper($employee?->name ?? 'PEGAWAI').' - '.$year,
            ['Bulan', 'Hadir', 'Telat (kali)', 'Menit Telat', 'Pulang Cepat', 'Hari Kerja'],
            $rows,
            [16, 10, 13, 13, 14, 11],
        );

        return $this->stream($path, 'performa-'.Str::slug($employee?->name ?? 'pegawai').'-'.$year.'.xlsx');
    }

    /* =====================================================================
     | Rekap Tahunan
     ===================================================================== */

    public function tahunan(Request $request): View
    {
        $service = app(AttendancePerformanceService::class);
        $year = (int) $request->query('tahun', now()->year);

        return view('analitik.tahunan', [
            'year' => $year,
            'monthly' => $service->monthlyTotals($year),
            'rows' => $service->yearlyByEmployee($year),
            'availableYears' => $service->availableYears(),
            'monthLabels' => self::MONTH_LABELS,
        ]);
    }

    public function exportTahunan(Request $request): StreamedResponse
    {
        $service = app(AttendancePerformanceService::class);
        $year = (int) $request->query('tahun', now()->year);

        $rows = [];
        foreach ($service->yearlyByEmployee($year) as $i => $row) {
            $hadir = (int) $row->hadir;
            $tepat = $hadir > 0 ? round(($hadir - (int) $row->telat) / $hadir * 100, 1) : 0;
            $rows[] = [$i + 1, $row->employee?->name ?? '-', $hadir, (int) $row->telat, (int) $row->menit_telat, (int) $row->pulang, $tepat.'%'];
        }

        $path = app(GenericExcelBuilder::class)->build(
            'REKAP TAHUNAN ABSENSI - '.$year,
            ['#', 'Pegawai', 'Hadir', 'Telat (kali)', 'Menit Telat', 'Pulang Cepat', 'Ketepatan'],
            $rows,
            [6, 28, 10, 13, 13, 14, 12],
        );

        return $this->stream($path, 'rekap-tahunan-'.$year.'.xlsx');
    }

    /* =====================================================================
     | Bantuan
     ===================================================================== */

    protected function stream(string $path, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
