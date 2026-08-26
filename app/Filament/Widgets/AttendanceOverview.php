<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Syofyanzuhad\FilamentZktecoAdms\Models\Device;

class AttendanceOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $totalPegawai = Employee::where('is_admin', false)->count();
        $hadirHariIni = AttendanceRecap::whereDate('date', $today)->count();
        $telatHariIni = AttendanceRecap::whereDate('date', $today)->where('late_minutes', '>', 0)->count();
        $deviceOnline = Device::where('status', 'online')->count();

        return [
            Stat::make('Total Pegawai', $totalPegawai)
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Hadir Hari Ini', $hadirHariIni)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Telat Hari Ini', $telatHariIni)
                ->icon('heroicon-o-clock')
                ->color($telatHariIni > 0 ? 'danger' : 'success'),
            Stat::make('Device Online', $deviceOnline)
                ->icon('heroicon-o-wifi')
                ->color($deviceOnline > 0 ? 'success' : 'danger'),
        ];
    }
}
