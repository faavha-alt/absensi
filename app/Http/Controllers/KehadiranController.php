<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class KehadiranController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->filled('tanggal')
            ? Carbon::parse($request->query('tanggal'))->startOfDay()
            : Carbon::today();

        $dept = $request->query('departemen');
        $status = $request->query('status'); // hadir|telat|belum|pulang

        $employees = Employee::query()
            ->where('is_admin', false)
            ->when($dept, fn ($q) => $q->where('department', $dept))
            ->orderBy('name')
            ->get();

        $recaps = AttendanceRecap::whereDate('date', $date)->get()->keyBy('employee_id');

        $rows = $employees->map(function (Employee $e) use ($recaps) {
            $recap = $recaps->get($e->id);

            return [
                'employee' => $e,
                'recap' => $recap,
                'status' => $recap ? $recap->status() : ['key' => 'belum', 'label' => 'Belum Absen', 'class' => 'badge-alpa'],
                'duration' => ($recap && $recap->check_in && $recap->check_out)
                    ? $recap->check_in->diffInMinutes($recap->check_out)
                    : null,
            ];
        });

        $counts = [
            'hadir' => $rows->filter(fn ($r) => $r['status']['key'] === 'hadir')->count(),
            'telat' => $rows->filter(fn ($r) => $r['status']['key'] === 'telat')->count(),
            'pulang' => $rows->filter(fn ($r) => $r['status']['key'] === 'pulang_cepat')->count(),
            'belum' => $rows->filter(fn ($r) => $r['status']['key'] === 'belum')->count(),
        ];

        if ($status) {
            $rows = $rows->filter(function ($r) use ($status) {
                return match ($status) {
                    'hadir' => in_array($r['status']['key'], ['hadir'], true),
                    'telat' => $r['status']['key'] === 'telat',
                    'pulang' => $r['status']['key'] === 'pulang_cepat',
                    'belum' => $r['status']['key'] === 'belum',
                    default => true,
                };
            })->values();
        }

        return view('kehadiran.index', [
            'date' => $date,
            'rows' => $rows,
            'counts' => $counts,
            'dept' => $dept,
            'status' => $status,
            'departments' => Employee::where('is_admin', false)->whereNotNull('department')->where('department', '!=', '')->distinct()->orderBy('department')->pluck('department'),
            'holiday' => Holiday::whereDate('date', $date)->first(),
            'isWeekend' => $date->isWeekend(),
        ]);
    }
}
