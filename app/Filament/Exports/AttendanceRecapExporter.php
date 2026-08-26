<?php

namespace App\Filament\Exports;

use App\Models\AttendanceRecap;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class AttendanceRecapExporter extends Exporter
{
    protected static ?string $model = AttendanceRecap::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee.name')
                ->label('Pegawai'),
            ExportColumn::make('date')
                ->label('Tanggal')
                ->formatStateUsing(fn ($state) => $state?->format('Y-m-d')),
            ExportColumn::make('check_in')
                ->label('Jam Masuk')
                ->formatStateUsing(fn ($state) => $state?->format('H:i')),
            ExportColumn::make('check_out')
                ->label('Jam Pulang')
                ->formatStateUsing(fn ($state) => $state?->format('H:i') ?? '-'),
            ExportColumn::make('late_minutes')
                ->label('Telat (menit)'),
            ExportColumn::make('early_leave_minutes')
                ->label('Pulang Cepat (menit)'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export rekap absensi selesai, '.Str::of('baris')->plural($export->successful_rows).' ('.$export->successful_rows.') diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.$failedRowsCount.' baris gagal.';
        }

        return $body;
    }
}
