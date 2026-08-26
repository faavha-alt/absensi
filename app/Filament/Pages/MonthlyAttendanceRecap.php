<?php

namespace App\Filament\Pages;

use App\Models\AttendanceRecap;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class MonthlyAttendanceRecap extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Rekap Bulanan';

    protected static ?string $title = 'Rekap Bulanan';

    protected string $view = 'filament.pages.monthly-attendance-recap';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'month' => now()->startOfMonth()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('month')
                    ->label('Bulan')
                    ->displayFormat('F Y')
                    ->native(false)
                    ->live(),
            ])
            ->statePath('data');
    }

    public function getRows(): \Illuminate\Support\Collection
    {
        $month = Carbon::parse($this->data['month'] ?? now())->startOfMonth();

        return AttendanceRecap::query()
            ->selectRaw('employee_id, COUNT(*) as total_hadir, SUM(late_minutes > 0) as jumlah_telat, SUM(late_minutes) as total_menit_telat, SUM(early_leave_minutes > 0) as jumlah_pulang_cepat')
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->groupBy('employee_id')
            ->with('employee')
            ->get()
            ->sortByDesc('jumlah_telat')
            ->values();
    }
}
