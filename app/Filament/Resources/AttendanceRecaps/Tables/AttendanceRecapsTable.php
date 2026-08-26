<?php

namespace App\Filament\Resources\AttendanceRecaps\Tables;

use App\Models\Employee;
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
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
