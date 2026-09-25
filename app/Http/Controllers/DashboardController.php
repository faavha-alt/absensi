<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Syofyanzuhad\FilamentZktecoAdms\Models\AttendanceLog;
use Syofyanzuhad\FilamentZktecoAdms\Models\Device;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $days = (int) $request->query('range', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        $employees = Employee::where('is_admin', false)->orderBy('name')->get();

        $recapsToday = AttendanceRecap::whereDate('date', $today)
            ->get()
            ->keyBy('employee_id');

        $hadir = $recapsToday->count();
        $telat = $recapsToday->where('late_minutes', '>', 0)->count();
        $pulang = $recapsToday->where('early_leave_minutes', '>', 0)->count();

        // Kehadiran hari ini (per pegawai)
        $todayRows = $employees->map(function (Employee $e) use ($recapsToday) {
            $recap = $recapsToday->get($e->id);

            return [
                'employee' => $e,
                'recap' => $recap,
                'status' => $recap
                    ? $recap->status()
                    : ['key' => 'belum', 'label' => 'Belum Absen', 'class' => 'st-alpa'],
            ];
        });

        // Tren harian
        $from = $today->copy()->subDays($days - 1);
        $byDate = AttendanceRecap::query()
            ->selectRaw('date, COUNT(*) as hadir, SUM(late_minutes > 0) as telat')
            ->whereBetween('date', [$from->toDateString(), $today->toDateString()])
            ->groupBy('date')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->date)->format('Y-m-d'));

        $trendLabels = [];
        $trendHadir = [];
        $trendTelat = [];

        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i);
            $key = $d->format('Y-m-d');
            $trendLabels[] = $d->format('d/m');
            $trendHadir[] = (int) ($byDate->get($key)->hadir ?? 0);
            $trendTelat[] = (int) ($byDate->get($key)->telat ?? 0);
        }

        // Scan terbaru
        $recent = AttendanceLog::query()
            ->orderByDesc('punched_at')
            ->limit(8)
            ->get();

        $pinToName = Employee::whereNotNull('zkteco_pin')->pluck('name', 'zkteco_pin');

        return view('dashboard.index', [
            'today' => $today,
            'range' => $days,
            'stats' => [
                'pegawai' => $employees->count(),
                'hadir' => $hadir,
                'telat' => $telat,
                'pulang' => $pulang,
                'belum' => max(0, $employees->count() - $hadir),
                'device_online' => Device::where('status', 'online')->count(),
                'device_total' => Device::count(),
            ],
            'todayRows' => $todayRows,
            'trend' => [
                'labels' => $trendLabels,
                'hadir' => $trendHadir,
                'telat' => $trendTelat,
            ],
            'recent' => $recent,
            'pinToName' => $pinToName,
        ]);
    }
}
