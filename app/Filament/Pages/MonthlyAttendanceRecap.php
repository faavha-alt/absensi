<?php

namespace App\Filament\Pages;

use App\Models\AttendanceRecap;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyAttendanceRecap extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Rekap Bulanan';

    protected static ?string $title = 'Rekap Bulanan';

    protected string $view = 'filament.pages.monthly-attendance-recap';

    public ?array $data = [];

    protected static $bulanOptions = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function mount(): void
    {
        $this->form->fill([
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('bulan')
                    ->label('Bulan')
                    ->options(self::$bulanOptions)
                    ->native(false)
                    ->live()
                    ->required(),
                Select::make('tahun')
                    ->label('Tahun')
                    ->options(fn () => $this->availableYears())
                    ->native(false)
                    ->live()
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $bulan = (int) ($this->data['bulan'] ?? now()->month);
        $tahun = (int) ($this->data['tahun'] ?? now()->year);
        $rows = $this->getRows();

        $filename = 'rekap-bulanan-'.$tahun.'-'.str_pad((string) $bulan, 2, '0', STR_PAD_LEFT).'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Pegawai', 'Jumlah Hadir', 'Jumlah Telat', 'Total Menit Telat', 'Jumlah Pulang Cepat'], ',', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->employee?->name ?? '-',
                    $row->total_hadir,
                    $row->jumlah_telat,
                    $row->total_menit_telat,
                    $row->jumlah_pulang_cepat,
                ], ',', '"', '\\');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function availableYears(): array
    {
        $min = AttendanceRecap::min('date');
        $startYear = $min ? Carbon::parse($min)->year : now()->year;
        $endYear = now()->year;

        return collect(range($endYear, $startYear))->mapWithKeys(fn ($y) => [$y => $y])->all();
    }

    public function getRows(): Collection
    {
        $bulan = (int) ($this->data['bulan'] ?? now()->month);
        $tahun = (int) ($this->data['tahun'] ?? now()->year);

        return AttendanceRecap::query()
            ->selectRaw('employee_id, COUNT(*) as total_hadir, SUM(late_minutes > 0) as jumlah_telat, SUM(late_minutes) as total_menit_telat, SUM(early_leave_minutes > 0) as jumlah_pulang_cepat')
            ->whereYear('date', $tahun)
            ->whereMonth('date', $bulan)
            ->groupBy('employee_id')
            ->with('employee')
            ->get()
            ->sortByDesc('jumlah_telat')
            ->values();
    }
}
