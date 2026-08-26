<?php

namespace App\Filament\Resources\AttendanceRecaps;

use App\Filament\Resources\AttendanceRecaps\Pages\ListAttendanceRecaps;
use App\Filament\Resources\AttendanceRecaps\Pages\ViewAttendanceRecap;
use App\Filament\Resources\AttendanceRecaps\Schemas\AttendanceRecapInfolist;
use App\Filament\Resources\AttendanceRecaps\Tables\AttendanceRecapsTable;
use App\Models\AttendanceRecap;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceRecapResource extends Resource
{
    protected static ?string $model = AttendanceRecap::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Rekap Absensi';

    protected static ?string $modelLabel = 'Rekap Absensi';

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceRecapInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceRecapsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceRecaps::route('/'),
            'view' => ViewAttendanceRecap::route('/{record}'),
        ];
    }
}
