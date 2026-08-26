<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecap extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'late_minutes',
        'early_leave_minutes',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isLate(): bool
    {
        return $this->late_minutes > 0;
    }

    public function isEarlyLeave(): bool
    {
        return $this->early_leave_minutes > 0;
    }
}
