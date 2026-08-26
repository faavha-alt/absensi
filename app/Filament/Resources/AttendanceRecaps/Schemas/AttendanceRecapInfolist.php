<?php

namespace App\Filament\Resources\AttendanceRecaps\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceRecapInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.name')
                    ->label('Employee'),
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('check_in')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('check_out')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('late_minutes')
                    ->numeric(),
                TextEntry::make('early_leave_minutes')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
