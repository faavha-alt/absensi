<?php

namespace App\Filament\Pages;

use App\Models\WorkSchedule;
use App\Services\AttendanceRecapCalculator;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class WorkScheduleSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Jam Kerja';

    protected static ?string $title = 'Pengaturan Jam Kerja';

    protected string $view = 'filament.pages.work-schedule-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $schedule = WorkSchedule::current();

        $this->form->fill([
            'time_in' => $schedule->time_in->format('H:i'),
            'time_out' => $schedule->time_out->format('H:i'),
            'late_tolerance_minutes' => $schedule->late_tolerance_minutes,
            'early_leave_tolerance_minutes' => $schedule->early_leave_tolerance_minutes,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TimePicker::make('time_in')
                    ->label('Jam Masuk')
                    ->seconds(false)
                    ->required(),
                TextInput::make('late_tolerance_minutes')
                    ->label('Toleransi Keterlambatan (menit)')
                    ->helperText('Pegawai dianggap telat jika scan masuk melewati jam masuk + toleransi ini.')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TimePicker::make('time_out')
                    ->label('Jam Pulang')
                    ->seconds(false)
                    ->required(),
                TextInput::make('early_leave_tolerance_minutes')
                    ->label('Toleransi Pulang Cepat (menit)')
                    ->helperText('Pegawai dianggap pulang cepat jika scan pulang lebih awal dari jam pulang dikurangi toleransi ini.')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $schedule = WorkSchedule::current();
        $schedule->update([
            'time_in' => $data['time_in'],
            'time_out' => $data['time_out'],
            'late_tolerance_minutes' => $data['late_tolerance_minutes'],
            'early_leave_tolerance_minutes' => $data['early_leave_tolerance_minutes'],
        ]);

        app(AttendanceRecapCalculator::class)->recomputeAll();

        Notification::make()
            ->title('Pengaturan disimpan & rekap absensi dihitung ulang')
            ->success()
            ->send();
    }
}
