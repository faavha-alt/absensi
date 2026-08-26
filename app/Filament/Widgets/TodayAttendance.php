<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TodayAttendance extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'filament.widgets.today-attendance';

    public function getRows(): Collection
    {
        $today = Carbon::today();

        $recaps = AttendanceRecap::whereDate('date', $today)
            ->get()
            ->keyBy('employee_id');

        return Employee::where('is_admin', false)
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($recaps) {
                $recap = $recaps->get($employee->id);

                if (! $recap) {
                    return [
                        'employee' => $employee->name,
                        'check_in' => null,
                        'status' => ['key' => 'belum_hadir', 'label' => 'Belum Hadir', 'class' => 'st-alpa'],
                    ];
                }

                return [
                    'employee' => $employee->name,
                    'check_in' => $recap->check_in,
                    'status' => $recap->status(),
                ];
            })
            ->sortBy(fn (array $row) => $row['check_in'] ? 0 : 1)
            ->values();
    }
}
