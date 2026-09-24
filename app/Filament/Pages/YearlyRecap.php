<?php

namespace App\Filament\Pages;

use App\Services\AttendancePerformanceService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class YearlyRecap extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?string $navigationLabel = 'Rekap Tahunan';

    protected static ?string $title = 'Rekap Tahunan';

    protected static ?int $navigationSort = 61;

    protected string $view = 'filament.pages.yearly-recap';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['tahun' => now()->year]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tahun')
                    ->label('Tahun')
                    ->options(fn () => collect(app(AttendancePerformanceService::class)->availableYears())
                        ->mapWithKeys(fn ($y) => [$y => $y]))
                    ->native(false)
                    ->live()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function getYear(): int
    {
        return (int) ($this->data['tahun'] ?? now()->year);
    }

    /**
     * @return array<int, array{hadir:int, telat:int, pulang:int, menit_telat:int}>
     */
    public function getMonthly(): array
    {
        return app(AttendancePerformanceService::class)->monthlyTotals($this->getYear());
    }

    public function getRows(): Collection
    {
        return app(AttendancePerformanceService::class)->yearlyByEmployee($this->getYear());
    }

    /**
     * @return array<string, int>
     */
    public function getSummary(): array
    {
        $m = $this->getMonthly();

        return [
            'hadir' => array_sum(array_column($m, 'hadir')),
            'telat' => array_sum(array_column($m, 'telat')),
            'pulang' => array_sum(array_column($m, 'pulang')),
            'menit_telat' => array_sum(array_column($m, 'menit_telat')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function barConfig(): array
    {
        $m = array_values($this->getMonthly());

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $this->monthLabels(),
                'datasets' => [
                    [
                        'label' => 'Hadir',
                        'data' => array_map(fn ($x) => $x['hadir'], $m),
                        'backgroundColor' => '#2fb380',
                        'borderRadius' => 6,
                    ],
                    [
                        'label' => 'Telat',
                        'data' => array_map(fn ($x) => $x['telat'], $m),
                        'backgroundColor' => '#dc3545',
                        'borderRadius' => 6,
                    ],
                    [
                        'label' => 'Pulang Cepat',
                        'data' => array_map(fn ($x) => $x['pulang'], $m),
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
     * @return array<string, mixed>
     */
    public function lineConfig(): array
    {
        $m = array_values($this->getMonthly());

        return [
            'type' => 'line',
            'data' => [
                'labels' => $this->monthLabels(),
                'datasets' => [[
                    'label' => 'Menit Telat',
                    'data' => array_map(fn ($x) => $x['menit_telat'], $m),
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
