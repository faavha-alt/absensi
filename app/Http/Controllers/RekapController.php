<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use App\Services\AttendancePerformanceService;
use App\Services\DailyRecapExcelBuilder;
use App\Services\MonthlyRecapExcelBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapController extends Controller
{
    /* =====================================================================
     | Rekap Harian
     ===================================================================== */

    public function harian(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        return view('rekap.harian', [
            'rows' => $this->dailyQuery($request, $from, $to)->paginate(30)->withQueryString(),
            'summary' => $this->dailySummary($request, $from, $to),
            'employees' => Employee::where('is_admin', false)->orderBy('name')->pluck('name', 'id'),
            'from' => $from,
            'to' => $to,
            'filters' => [
                'dari' => $from->toDateString(),
                'sampai' => $to->toDateString(),
                'pegawai' => $request->query('pegawai'),
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function exportHarian(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $rows = $this->dailyQuery($request, $from, $to)->get();

        $periode = $from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y');
        $path = app(DailyRecapExcelBuilder::class)->build($rows, $periode);
        $filename = 'rekap-harian-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx';

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /* =====================================================================
     | Rekap Bulanan
     ===================================================================== */

    public function bulanan(Request $request): View
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);
        $month = Carbon::create($tahun, $bulan, 1);

        $service = app(AttendancePerformanceService::class);

        $totals = $service->monthlyTotals($tahun)[$bulan];

        return view('rekap.bulanan', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'month' => $month,
            'matrix' => $service->matrixForMonth($month),
            'rows' => $service->monthlyByEmployee($tahun, $bulan),
            'totals' => $totals,
            'prevMonth' => $month->copy()->subMonth(),
            'nextMonth' => $month->copy()->addMonth(),
            'availableYears' => $service->availableYears(),
        ]);
    }

    public function exportBulanan(Request $request): StreamedResponse
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $service = app(AttendancePerformanceService::class);
        $rows = $service->monthlyByEmployee($tahun, $bulan);

        $bulanNama = Carbon::create($tahun, $bulan, 1)->translatedFormat('F');
        $path = app(MonthlyRecapExcelBuilder::class)->build($rows, $bulanNama, $tahun);
        $filename = 'rekap-bulanan-'.$tahun.'-'.str_pad((string) $bulan, 2, '0', STR_PAD_LEFT).'.xlsx';

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /* =====================================================================
     | Bantuan
     ===================================================================== */

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveRange(Request $request): array
    {
        $from = $request->filled('dari')
            ? Carbon::parse($request->query('dari'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('sampai')
            ? Carbon::parse($request->query('sampai'))->startOfDay()
            : now()->endOfMonth();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    protected function dailyQuery(Request $request, Carbon $from, Carbon $to): Builder
    {
        return AttendanceRecap::query()
            ->with('employee')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('pegawai'), fn (Builder $q) => $q->where('employee_id', $request->integer('pegawai')))
            ->when($request->query('status') === 'telat', fn (Builder $q) => $q->where('late_minutes', '>', 0))
            ->when($request->query('status') === 'pulang', fn (Builder $q) => $q->where('early_leave_minutes', '>', 0))
            ->orderByDesc('date')
            ->orderBy('employee_id');
    }

    /**
     * @return array<string, int>
     */
    protected function dailySummary(Request $request, Carbon $from, Carbon $to): array
    {
        $agg = $this->dailyQuery($request, $from, $to)
            ->reorder()
            ->selectRaw('COUNT(*) as hadir, SUM(late_minutes > 0) as telat, SUM(early_leave_minutes > 0) as pulang, SUM(late_minutes) as menit_telat')
            ->first();

        return [
            'hadir' => (int) ($agg->hadir ?? 0),
            'telat' => (int) ($agg->telat ?? 0),
            'pulang' => (int) ($agg->pulang ?? 0),
            'menit_telat' => (int) ($agg->menit_telat ?? 0),
        ];
    }
}
