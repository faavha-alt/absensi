<?php

namespace App\Filament\Resources\AttendanceRecaps\Tables;

use App\Filament\Exports\AttendanceRecapExporter;
use App\Models\AttendanceRecap;
use App\Models\Employee;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class AttendanceRecapsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('check_in')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn (AttendanceRecap $record): string => $record->status()['label'])
                    ->badge()
                    ->color(fn (AttendanceRecap $record): string => match ($record->status()['key']) {
                        'telat' => 'danger',
                        'pulang_cepat' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('late_minutes')
                    ->label('Telat (menit)')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                    ->badge(),
                TextColumn::make('early_leave_minutes')
                    ->label('Pulang Cepat (menit)')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success')
                    ->badge(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Pegawai')
                    ->options(fn () => Employee::pluck('name', 'id')),
                Filter::make('month')
                    ->form([
                        DatePicker::make('month')
                            ->label('Bulan')
                            ->displayFormat('F Y')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['month']) {
                            return $query;
                        }

                        $month = Carbon::parse($data['month']);

                        return $query
                            ->whereYear('date', $month->year)
                            ->whereMonth('date', $month->month);
                    }),
                Filter::make('telat_saja')
                    ->label('Hanya yang telat')
                    ->query(fn ($query) => $query->where('late_minutes', '>', 0)),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(AttendanceRecapExporter::class)
                    ->label('Export'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
