<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $fillable = [
        'time_in',
        'time_out',
        'late_tolerance_minutes',
        'early_leave_tolerance_minutes',
    ];

    protected $casts = [
        'time_in' => 'datetime:H:i',
        'time_out' => 'datetime:H:i',
    ];

    public static function current(): self
    {
        return static::first() ?? static::create([
            'time_in' => '08:00:00',
            'time_out' => '16:00:00',
            'late_tolerance_minutes' => 0,
            'early_leave_tolerance_minutes' => 0,
        ]);
    }
}
