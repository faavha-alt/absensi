<?php

namespace App\Console\Commands;

use App\Services\AttendanceRecapCalculator;
use Illuminate\Console\Command;

class RecomputeAttendanceRecaps extends Command
{
    protected $signature = 'attendance:recompute-recaps';

    protected $description = 'Hitung ulang rekap absensi harian (hadir/telat/pulang cepat) untuk semua pegawai dari log mentah';

    public function handle(AttendanceRecapCalculator $calculator): int
    {
        $count = $calculator->recomputeAll();

        $this->info("Selesai. {$count} hari-pegawai dihitung ulang.");

        return self::SUCCESS;
    }
}
