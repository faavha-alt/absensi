<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Syofyanzuhad\FilamentZktecoAdms\Models\AttendanceLog;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'employee_code',
        'position',
        'department',
        'zkteco_pin',
        'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'pin', 'zkteco_pin');
    }
}
