<?php

namespace App\Filament\Resources\AttendanceRecaps\Pages;

use App\Filament\Resources\AttendanceRecaps\AttendanceRecapResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceRecap extends ViewRecord
{
    protected static string $resource = AttendanceRecapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
