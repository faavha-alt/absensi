<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('employee_code')
                    ->label('Kode/NIP')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('position')
                    ->label('Jabatan'),
                TextInput::make('department')
                    ->label('Departemen'),
                TextInput::make('zkteco_pin')
                    ->label('PIN ZKTeco')
                    ->helperText('PIN pegawai sesuai yang terdaftar di alat MB20 (lihat menu ZKTeco ADMS > Zkteco Users setelah pegawai pernah absen).')
                    ->unique(ignoreRecord: true),
            ]);
    }
}
