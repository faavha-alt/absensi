<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Syofyanzuhad\FilamentZktecoAdms\Models\AttendanceLog;
use Syofyanzuhad\FilamentZktecoAdms\Models\Device;

class PerangkatController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();

        return view('perangkat.index', [
            'devices' => Device::orderBy('serial_number')->get(),
            'recent' => AttendanceLog::orderByDesc('punched_at')->limit(30)->get(),
            'pinToName' => Employee::whereNotNull('zkteco_pin')->pluck('name', 'zkteco_pin'),
            'todayScans' => AttendanceLog::whereDate('punched_at', $today)->count(),
            'totalScans' => AttendanceLog::count(),
            'lastScan' => AttendanceLog::orderByDesc('punched_at')->value('punched_at'),
        ]);
    }
}
