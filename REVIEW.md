# Review Kode — Sistem Absensi Pegawai

> Tanggal: 2026-08-26. Reviewer: sesi AI. Cakupan: seluruh `app/` + `database/` + `routes/` + `resources/views/`.
> Tujuan: identifikasi kekurangan/risiko & desain perbaikan. Implementasi dilakukan di Claude Code (lihat PROGRESS.md).
> Verifikasi: kode tidak bisa dijalankan lokal di server AI ini (PHP tidak terpasang); app berjalan di CloudPanel. Bagian yang bergantung pada package vendor (`vendor/syofyanzuhad/*`) tidak bisa dicek di sini.

## Prioritas tinggi

### 🔴 1. False-positive "pulang cepat" (jendela check-out tidak divalidasi)
- **Lokasi**: `app/Services/AttendanceRecapCalculator.php:37,51-59`
- **Masalah**: `$checkOut = $logs->count() > 1 ? $logs->last() : null` tanpa memeriksa kapan scan terakhir terjadi. Jika pegawai hanya melakukan scan pagi berkali-kali (mis. scan ulang jam 10:00) tanpa scan pulang, `check_out` = 10:00 → dihitung "pulang cepat" besar (jam 08:00–16:00 → ~6 jam). Ini salah tangkap dan bakal mengotori rekap.
- **Desain perbaikan**: anggap `check_out` valid hanya jika scan terakhir jatuh di "jendela pulang" yang masuk akal, mis. **setelah tengah hari (≥ 12:00)** ATAU **setidaknya N jam setelah check_in** (mis. ≥ 4 jam). Jika tidak, set `check_out = null` (sama seperti kasus 1 scan). Taruh aturan ini sebagai konstanta/boundary yang mudah disesuaikan.

### 🔴 2. Endpoint `/iclock/*` tanpa otentikasi → potensi injeksi absensi palsu
- **Lokasi**: `config/zkteco-adms.php` (routes), endpoint push dari device (`/iclock/cdata`, `/iclock/getrequest`, `/iclock/devicecmd`).
- **Masalah**: situs publik (`https://absensi.mipa.uns.ac.id`). Endpoint ADMS menerima push tanpa token; `device.auto_register` = true sehingga device baru otomatis didaftarkan. Siapa pun yang tahu format protokol bisa mengirim ATTLOG palsu (absen fiktif / manipulasi kehadiran).
- **Desain perbaikan**:
  - Pertahankan auto-register tapi **validasi `SN`**: tolak / tandai device yang tidak dikenali (opsi `auto_register` dibuat configurable, default tetap true untuk kemudahan setup awal).
  - Opsional **shared secret / token** yang dikirim device (header khusus) untuk memperkuat, atau **batasi IP** sumber device (whitelist di firewall CloudPanel/Nginx).
  - Minimal: log semua request `/iclock` (SN, IP, payload) untuk audit.

## Prioritas sedang

### 🟡 3. Hari libur nasional tidak ditangani
- **Lokasi**: `app/Services/AttendanceRecapCalculator.php:15` (`$date->isWeekend()`).
- **Masalah**: hanya Sabtu/Minggu yang dikecualikan. Hari libur nasional di hari kerja dihitung sebagai tidak hadir, dan pegawai yang hadir di hari libur tidak direkap.
- **Desain**: tabel `holidays` (`id`, `date` unik, `name`, `is_national`?) yang dikelola admin (Filament resource sederhana) + seeder hari libur nasional tahun berjalan. `AttendanceRecapCalculator` skip hari yang ada di tabel `holidays`.

### 🟡 4. Export rekap (Excel/PDF) belum ada
- **Lokasi**: `app/Filament/Pages/MonthlyAttendanceRecap.php`, `AttendanceRecapsTable`.
- **Masalah**: rekap tidak bisa diunduh untuk arsip/pelaporan.
- **Desain**: tombol "Export" pada Rekap Bulanan (agregat per pegawai per bulan) dan Rekap Harian (filter aktif). Gunakan `maatwebsite/excel` atau `axon-archive/filament-export`; sertakan kolom: pegawai, hadir, telat, menit telat, pulang cepat.

### 🟡 5. Recompute sinkron per-log saat device push batch → lambat & rawan retry
- **Lokasi**: `app/Listeners/RecomputeAttendanceRecap.php`, `AppServiceProvider::boot()`.
- **Masalah**: listener jalan sinkron di dalam request `/iclock`. Saat device push beberapa log sekaligus, tiap log memicu recompute (bisa berkali recompute untuk tanggal yang sama), memperlambat respons ke device (device menunggu → timeout/retry).
- **Desain**: kumpulkan `(employee_id, date)` unik dari batch push, lalu recompute sekali per pasangan. Bisa via queue job (coalesce dengan `shouldQueue` + dispatching ke job yang menyatukan, atau sesederhana debounce di sisi aplikasi). Untuk skala kecil saat ini, minimal pastikan recompute per (employee,date) tidak dijalankan ganda dalam satu request.

### 🟡 6. Tidak ada scheduler untuk rekonsiliasi rekap
- **Lokasi**: `routes/console.php` (hanya `inspire`).
- **Masalah**: command `RecomputeAttendanceRecaps` ada tapi tidak dijadwalkan. Jika ada event yang terlewat (device offline lalu push backlog, restart server), rekap bisa basi.
- **Desain**: jadwalkan recompute harian via Laravel Scheduler (`$schedule->command('app:recompute-attendance-recaps')->dailyAt('04:00')`) + jalankan cron `schedule:run` di CloudPanel. Alternatif: recompute saat import manual.

### 🟡 7. Time zone tidak dikonsistensikan
- **Lokasi**: `AttendanceRecapCalculator` (`whereDate`, `isWeekend`, `setTimeFromTimeString`).
- **Masalah**: `whereDate('punched_at', $date)` dan `$date->isWeekend()` bergantung `APP_TIMEZONE` / timezone DB. Jika zona server/device berbeda (device punya zona sendiri), pengelompokan tanggal bisa geser (absensi sore jadi "besok").
- **Desain**: pastikan `APP_TIMEZONE=Asia/Jakarta` konsisten dengan zona device; verifikasi perbandingan `check_in` terhadap jam kerja memakai timezone lokal. Catat zona default device di `zkteco_devices`.

### 🟡 8. Kualitas data pegawai: `employee_code` masih = PIN, NIP/jabatan belum diisi
- **Lokasi**: `app/Filament/Resources/Employees/...`, data ter-import.
- **Masalah**: `employee_code` (label "Kode/NIP") masih berisi PIN, jabatan/departemen kosong → laporan rekap belum punya identitas resmi pegawai.
- **Desain**: lengkapi NIP resmi & jabatan/departemen tiap pegawai via form Employee (kolom sudah ada). Pertahankan `zkteco_pin` sebagai pemetaan ke alat.

### 🟡 9. Backup database belum ada
- **Lokasi**: operasional (CloudPanel).
- **Masalah**: tidak ada strategi backup MySQL untuk DB `absensi`.
- **Desain**: cron `mysqldump` harian + simpan ke storage offsite/eksternal (pola sama dengan backup stack AI di `server.md`). Tambahkan ke dokumen operasional.

### 🟡 10. Tidak ada test untuk logika rekap
- **Lokasi**: `tests/` (hanya ExampleTest default).
- **Masalah**: logika kehadiran (telat, pulang cepat, weekend, single-scan, admin) tidak punya uji otomatis → rawan regresi saat diubah.
- **Desain**: feature/unit test untuk `AttendanceRecapCalculator` & listener: hadir, telat (dengan/kon tanpa toleransi), pulang cepat (valid vs false-positive scan pagi), skip Sabtu/Minggu, skip libur (nanti), skip admin device, single-scan (check_out null).

## Prioritas rendah (nit / catatan)

- **`routes/web.php`** menampilkan halaman welcome default Laravel di `/` — pertimbangkan redirect ke `/admin` atau halaman info, agar URL publik tidak menampilkan halaman skeleton.
- **`AdminPanelProvider.php`** memuat CSS theme dengan `?v=5` hardcoded — gunakan versioning asset (mis. `filemtime`/Mix) agar cache tidak basi saat CSS berubah.
- **`AttendanceLogsRelationManager`** mengandalkan kolom `device.name`, `status`, `verify_type` dari package; tidak bisa diverifikasi di server AI ini (vendor tak terpasang lokal) — pastikan kolom & relasi `device` ada di model package saat deploy.
- **Duplikat log** dari re-push/retry device: log mentah bisa berisi baris ganda identik. Karena `check_in`/`check_out` diambil dari first/last, duplikat identik tidak mengubah rekap, tapi bisa menambah baris mentah; pertimbangkan unique index `(pin, punched_at)` bila perlu.

## Bagian yang belum diverifikasi
- Seluruh interaksi dengan package `syofyanzuhad/filament-zkteco-adms` (model, relasi, event) karena vendor tidak terpasang lokal.
- Render aktual halaman Filament di browser (tidak ada akses browser di sesi ini).
