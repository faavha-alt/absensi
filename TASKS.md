# Instruksi Kerja — Claude Code (Project absensi)

> Baca `REVIEW.md` untuk detail, `DESIGN.md` untuk desain. Kerjakan sesuai prioritas.
> Konteks: Laravel 12 + Filament 4 + package `syofyanzuhad/filament-zkteco-adms`, jalan di CloudPanel
> (`absensi.mipa.uns.ac.id`, PHP 8.5, MySQL `absensi`). Setelah tiap tugas: jalankan `php artisan test`,
> `./vendor/bin/pint` (rapikan), lalu git commit + push ke `origin` (master).

## TUGAS 1 — Perbaiki false-positive "pulang cepat" (prioritas tinggi)
- **File**: `app/Services/AttendanceRecapCalculator.php`
- **Masalah**: `check_out = logs->last()` dihitung tanpa jendela validasi → scan pagi berulang dianggap pulang cepat.
- **Lakukan**:
  1. Buat konstanta jendela (mis. `CHECKOUT_MIN_HOUR = 12`) dan/atau minimal selisih jam dari check-in (mis. ≥ 4 jam).
  2. `$checkOut` hanya dianggap valid jika scan terakhir memenuhi jendela tersebut; selain itu `null` (tidak hitung pulang cepat).
  3. Pastikan logika `early_leave_minutes` tetap jalan untuk check_out valid.
- **Terima jika**: scan ganda pagi hari tidak lagi menghasilkan "pulang cepat".

## TUGAS 2 — Amankan endpoint `/iclock/*` (prioritas tinggi)
- **File**: `config/zkteco-adms.php` + routing/middleware package.
- **Masalah**: endpoint publik, `auto_register` true, tanpa token → potensi injeksi absensi palsu.
- **Lakukan**:
  1. Pastikan device tak dikenal **tidak** didaftarkan otomatis (atau ditandai & ditolak) — validasi `SN` terhadap tabel `zkteco_devices`.
  2. Tambahkan proteksi tambahan: shared secret/token pada request, ATAU whitelist IP device di Nginx/ufw.
  3. Log semua request `/iclock` (SN, IP, payload) ke log/audit.
- **Terima jika**: push dari device terdaftar tetap normal; request SN tak dikenal ditolak/tercatat.

## TUGAS 3 — Dukungan hari libur nasional
- **Lakukan**: migration + model + Filament resource `Holiday` (date unik, name), seeder hari libur nasional tahun berjalan; `AttendanceRecapCalculator` skip tanggal libur.
- **Terima jika**: tanggal libur tidak direkap, resource Holiday tampil & mudah diisi admin.

## TUGAS 4 — Export rekap Excel/PDF
- **Lakukan**: tombol "Export" di Rekap Bulanan (`app/Filament/Pages/MonthlyAttendanceRecap.php`) dan Rekap Harian (`AttendanceRecapsTable`), pakai `maatwebsite/excel` atau `axon-archive/filament-export`.
- **Terima jika**: file terunduh dengan kolom pegawai, hadir, telat, menit telat, pulang cepat.

## TUGAS 5 — Scheduler rekonsiliasi rekap
- **File**: `routes/console.php`
- **Lakukan**: jadwalkan `RecomputeAttendanceRecaps` (dailyAt 04:00) + set cron `schedule:run` di CloudPanel.
- **Terima jika**: command berjalan terjadwal.

## TUGAS 6 — Optimasi recompute saat push batch
- **File**: `app/Listeners/RecomputeAttendanceRecaps.php`, `AppServiceProvider::boot()`
- **Lakukan**: coalesce recompute per (employee_id, date) agar tidak dijalankan ganda dalam satu request `/iclock` (mis. queue job / debounce).
- **Terima jika**: push banyak log tidak memicu recompute berulang untuk tanggal sama.

## TUGAS 7 — Konsistensi timezone
- **File**: `.env` (APP_TIMEZONE) + `AttendanceRecapCalculator`
- **Lakukan**: set `APP_TIMEZONE=Asia/Jakarta`, verifikasi `whereDate`/`isWeekend`/jam kerja konsisten dengan zona device.
- **Terima jika**: pengelompokan tanggal absensi benar di zona lokal.

## TUGAS 8 — Test otomatis logika rekap
- **Lakukan**: unit/feature test untuk `AttendanceRecapCalculator` & listener: hadir, telat (dgn/tanpa toleransi), pulang cepat (valid vs false-positive scan pagi), skip Sabtu/Minggu, skip libur, skip admin, single-scan.
- **Terima jika**: `php artisan test` lulus semua kasus.

## TUGAS 9 — Backup database
- **Lakukan**: cron `mysqldump` DB `absensi` harian + simpan ke storage eksternal/offsite. Tambahkan ke dokumentasi operasional.
- **Terima jika**: ada backup harian terverifikasi (restore test).

## TUGAS 10 — Rapikan data & minor
- Lengkapi NIP resmi & jabatan/departemen pegawai (employee_code saat ini = PIN).
- Redirect `/` → `/admin`.
- Versioning CSS (`?v=5` hardcoded di `AdminPanelProvider.php`).

---
**Verifikasi akhir**: `php artisan test` hijau, `pint` bersih, push ke GitHub. Update checklist `PROGRESS.md` (centang item yang selesai).

> **Catatan**: bagian **UI TIDAK termasuk tugas di file ini** — UI direncanakan & dikerjakan oleh sesi AI (lihat `PROGRESS.md` §"Tasks — UI" & `DESIGN.md` §"Desain UI"). Jangan dikerjakan di Claude Code agar tidak tumpang-tindih.
