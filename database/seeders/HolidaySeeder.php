<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Hari libur nasional & cuti bersama 2026 sesuai SKB 3 Menteri
     * (Menag No. 1497/2025, Menaker No. 2/2025, MenPAN-RB No. 5/2025).
     */
    public function run(): void
    {
        $holidays = [
            ['2026-01-01', 'Tahun Baru Masehi 2026'],
            ['2026-01-16', "Isra' Mi'raj Nabi Muhammad SAW"],
            ['2026-02-16', 'Cuti Bersama Tahun Baru Imlek'],
            ['2026-02-17', 'Tahun Baru Imlek 2577 Kongzili'],
            ['2026-03-18', 'Cuti Bersama Hari Suci Nyepi'],
            ['2026-03-19', 'Hari Suci Nyepi Tahun Baru Saka 1948'],
            ['2026-03-20', 'Cuti Bersama Hari Raya Idul Fitri'],
            ['2026-03-21', 'Hari Raya Idul Fitri 1447 H'],
            ['2026-03-22', 'Hari Raya Idul Fitri 1447 H (hari ke-2)'],
            ['2026-03-23', 'Cuti Bersama Hari Raya Idul Fitri'],
            ['2026-03-24', 'Cuti Bersama Hari Raya Idul Fitri'],
            ['2026-04-03', 'Wafat Yesus Kristus'],
            ['2026-04-05', 'Kebangkitan Yesus Kristus (Paskah)'],
            ['2026-05-01', 'Hari Buruh Internasional'],
            ['2026-05-14', 'Kenaikan Yesus Kristus'],
            ['2026-05-15', 'Cuti Bersama Kenaikan Yesus Kristus'],
            ['2026-05-27', 'Hari Raya Idul Adha 1447 H'],
            ['2026-05-28', 'Cuti Bersama Hari Raya Idul Adha'],
            ['2026-05-31', 'Hari Raya Waisak 2570 BE'],
            ['2026-06-01', 'Hari Lahir Pancasila'],
            ['2026-06-16', 'Tahun Baru Islam 1448 H'],
            ['2026-08-17', 'Hari Kemerdekaan Republik Indonesia'],
            ['2026-08-25', 'Maulid Nabi Muhammad SAW'],
            ['2026-12-24', 'Cuti Bersama Hari Raya Natal'],
            ['2026-12-25', 'Hari Raya Natal'],
        ];

        foreach ($holidays as [$date, $name]) {
            Holiday::updateOrCreate(['date' => $date], ['name' => $name]);
        }
    }
}
