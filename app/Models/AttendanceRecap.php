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
        'leave_type',
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

    /**
     * Status kehadiran untuk badge tampilan.
     * Prioritas: Izin/Sakit/Cuti/Dinas > Telat > Pulang Cepat > Hadir.
     *
     * @return array{key: string, label: string, class: string}
     */
    public function status(): array
    {
        if ($this->leave_type) {
            return match ($this->leave_type) {
                'sakit' => ['key' => 'sakit', 'label' => 'Sakit', 'class' => 'badge-sakit'],
                'cuti' => ['key' => 'cuti', 'label' => 'Cuti', 'class' => 'badge-cuti'],
                'dinas_luar' => ['key' => 'dinas_luar', 'label' => 'Dinas Luar', 'class' => 'badge-dinas'],
                default => ['key' => 'izin', 'label' => 'Izin', 'class' => 'badge-izin'],
            };
        }

        if ($this->late_minutes > 0) {
            return ['key' => 'telat', 'label' => 'Telat', 'class' => 'badge-telat'];
        }

        if ($this->early_leave_minutes > 0) {
            return ['key' => 'pulang_cepat', 'label' => 'Pulang Cepat', 'class' => 'badge-pulang'];
        }

        return ['key' => 'hadir', 'label' => 'Hadir', 'class' => 'badge-hadir'];
    }

    public function isLeave(): bool
    {
        return $this->leave_type !== null;
    }
}
