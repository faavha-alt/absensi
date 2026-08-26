<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\AttendanceRecap;
use App\Models\Holiday;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected string $view = 'filament.resources.employees.pages.view-employee';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    /**
     * Data kalender kehadiran bulan berjalan untuk pegawai ini.
     *
     * @return array<int, array{day:int, date:\Illuminate\Support\Carbon, weekday:string, status:array{c:string,label:string,title:string}}>
     */
    public function getCalendar(): array
    {
        $today = Carbon::today();

        $recaps = AttendanceRecap::where('employee_id', $this->record->id)
            ->whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->get()
            ->keyBy(fn (AttendanceRecap $r) => (int) $r->date->format('d'));

        $holidays = Holiday::whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->get()
            ->keyBy(fn (Holiday $h) => (int) $h->date->format('d'));

        $cellMap = [
            'hadir' => ['c' => 'c-hadir', 'label' => 'H'],
            'telat' => ['c' => 'c-telat', 'label' => 'T'],
            'pulang_cepat' => ['c' => 'c-pulang', 'label' => 'P'],
        ];

        $days = [];

        for ($d = 1; $d <= $today->daysInMonth; $d++) {
            $date = Carbon::create($today->year, $today->month, $d);
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
                'weekday' => $date->format('D'),
                'status' => $status,
            ];
        }

        return $days;
    }
}
