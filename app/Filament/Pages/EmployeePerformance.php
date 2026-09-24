<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Services\AttendancePerformanceService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EmployeePerformance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $navigationLabel = 'Performa Pegawai';

    protected static ?string $title = 'Performa Pegawai';

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.pages.employee-performance';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'employee_id' => Employee::where('is_admin', false)->orderBy('name')->value('id'),
            'tahun' => now()->year,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Pegawai')
                    ->options(fn () => Employee::where('is_admin', false)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->required(),
                Select::make('tahun')
                    ->label('Tahun')
                    ->options(fn () => collect(app(AttendancePerformanceService::class)->availableYears())
                        ->mapWithKeys(fn ($y) => [$y => $y]))
                    ->native(false)
                    ->live()
                    ->required(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    /**
     * @return array<int, array{hadir:int, telat:int, pulang:int, menit_telat:int, hari_kerja:int}>
     */
    public function getMonthly(): array
    {
        $employeeId = (int) ($this->data['employee_id'] ?? 0);
        $year = (int) ($this->data['tahun'] ?? now()->year);

        if (! $employeeId) {
            return [];
        }

        return app(AttendancePerformanceService::class)->monthlyForEmployee($employeeId, $year);
    }

    /**
     * @return array<string, float|int>
     */
    public function getSummary(): array
    {
        $monthly = $this->getMonthly();

        $hadir = array_sum(array_column($monthly, 'hadir'));
        $telat = array_sum(array_column($monthly, 'telat'));
        $pulang = array_sum(array_column($monthly, 'pulang'));
        $menitTelat = array_sum(array_column($monthly, 'menit_telat'));
        $hariKerja = array_sum(array_column($monthly, 'hari_kerja'));

        return [
            'hadir' => $hadir,
            'telat' => $telat,
            'pulang' => $pulang,
            'menit_telat' => $menitTelat,
            'hari_kerja' => $hariKerja,
            'persen_hadir' => $hariKerja > 0 ? round($hadir / $hariKerja * 100, 1) : 0,
            'persen_tepat' => $hadir > 0 ? round(($hadir - $telat) / $hadir * 100, 1) : 0,
        ];
    }

    public function getEmployeeName(): string
    {
        $employeeId = (int) ($this->data['employee_id'] ?? 0);

        return Employee::whereKey($employeeId)->value('name') ?? '-';
    }

    /**
     * Konfigurasi grafik batang bulanan (hadir/telat/pulang cepat).
     *
     * @return array<string, mixed>
     */
    public function barConfig(): array
    {
        $monthly = $this->getMonthly();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $this->monthLabels(),
                'datasets' => [
                    [
                        'label' => 'Hadir',
                        'data' => array_map(fn ($m) => $m['hadir'], array_values($monthly)),
                        'backgroundColor' => '#2fb380',
                        'borderRadius' => 6,
                    ],
                    [
                        'label' => 'Telat',
                        'data' => array_map(fn ($m) => $m['telat'], array_values($monthly)),
                        'backgroundColor' => '#dc3545',
                        'borderRadius' => 6,
                    ],
                    [
                        'label' => 'Pulang Cepat',
                        'data' => array_map(fn ($m) => $m['pulang'], array_values($monthly)),
                        'backgroundColor' => '#f59e0b',
                        'borderRadius' => 6,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'bottom']],
                'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            ],
        ];
    }

    /**
     * Konfigurasi grafik garis (total menit telat per bulan).
     *
     * @return array<string, mixed>
     */
    public function lineConfig(): array
    {
        $monthly = $this->getMonthly();

        return [
            'type' => 'line',
            'data' => [
                'labels' => $this->monthLabels(),
                'datasets' => [[
                    'label' => 'Menit Telat',
                    'data' => array_map(fn ($m) => $m['menit_telat'], array_values($monthly)),
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                    'pointRadius' => 3,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'bottom']],
                'scales' => ['y' => ['beginAtZero' => true]],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function monthLabels(): array
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    }
}
