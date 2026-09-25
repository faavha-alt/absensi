<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Leave extends Model
{
    /** Jenis izin yang dicatat manual oleh admin. */
    public const TYPES = [
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'cuti' => 'Cuti',
        'dinas_luar' => 'Dinas Luar',
    ];

    /** Inisial untuk matriks/kalender. */
    public const SHORT = [
        'izin' => 'I',
        'sakit' => 'S',
        'cuti' => 'C',
        'dinas_luar' => 'D',
    ];

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function label(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function short(): string
    {
        return self::SHORT[$this->type] ?? '?';
    }

    /** Kunci CSS: izin | sakit | cuti | dinas */
    public function cssKey(): string
    {
        return $this->type === 'dinas_luar' ? 'dinas' : $this->type;
    }

    public function badgeClass(): string
    {
        return 'badge-'.$this->cssKey();
    }

    public function cellClass(): string
    {
        return 'c-'.$this->cssKey();
    }

    public function covers(Carbon $date): bool
    {
        return $date->betweenIncluded($this->start_date->startOfDay(), $this->end_date->endOfDay());
    }

    public function dayCount(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Jenis izin untuk pegawai pada tanggal tertentu (atau null).
     */
    public static function typeForDate(int $employeeId, Carbon $date): ?string
    {
        $leave = static::query()
            ->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->first();

        return $leave?->type;
    }
}
