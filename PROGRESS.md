# Progress — absensi

Dibuat: 2026-08-26

## Tasks

<!-- Format checklist standar: "- [ ] belum" / "- [x] selesai". Dibaca otomatis oleh Project Dashboard (http://100.94.175.72:4400/) untuk menghitung progres. -->
- [x] Setup Laravel 12 + Filament 4 di server produksi (CloudPanel)
- [x] Integrasi ADMS ZKTeco MB20 (endpoint `/iclock/*`, teruji dengan simulasi push)
- [x] Model & Filament resource `Employee` + mapping ke `zkteco_pin`
- [x] Konfigurasi Comm > Cloud Server Setting di MB20 fisik agar push ke server
- [x] Verifikasi end-to-end dengan scan asli di MB20
- [x] Tarik data pegawai (USERINFO) & histori absensi lama (ATTLOG) dari memori device via command ADMS
- [x] Import 14 pegawai dari data device ke tabel `employees`
- [ ] Lengkapi kode/NIP resmi & jabatan/departemen tiap pegawai (sementara employee_code = PIN)
- [x] Pengaturan jam kerja & toleransi (global, berlaku semua pegawai)
- [x] Rekap harian otomatis (hadir/telat/pulang cepat) dari log mentah, auto-update real-time tiap ada scan baru
- [x] Rekap agregat bulanan per pegawai (total kali telat, total menit telat, total pulang cepat)
- [ ] Jadwal kerja per pegawai/departemen (v3, kalau kebutuhan berubah dari 1 jadwal global)
- [x] Export rekap (Excel via Filament ExportAction di Rekap Absensi; CSV di Rekap Bulanan)
- [x] Penanganan hari libur nasional (tabel `holidays` + resource admin + seeder 2026 + skip di rekap) — REVIEW 🔴 selesai
- [x] Perbaikan false-positive "pulang cepat" (validasi jendela check-out ≥ jam 12 atau ≥4 jam dari check-in) — REVIEW 🔴 selesai
- [x] Keamanan endpoint `/iclock/*` (auto_register dimatikan, SN tak dikenal ditolak, audit log, opsi IP allowlist) — REVIEW 🔴 selesai
- [x] Scheduler rekonsiliasi rekap (cron `schedule:run` tiap menit + `attendance:recompute-recaps` dailyAt 04:00) — REVIEW 🟡 selesai
- [x] Optimasi recompute saat device push batch (coalesce per (pegawai,tanggal) per request) — REVIEW 🟡 selesai
- [x] Konsistensi timezone (APP_TIMEZONE = Asia/Jakarta) — REVIEW 🟡 selesai
- [x] Test otomatis untuk `AttendanceRecapCalculator` (9 test, semua kasus dari REVIEW) — REVIEW 🟡 selesai
- [x] Backup database absensi (cron `mysqldump` harian jam 02:00 via `clpctl db:export`, retensi 14 hari, lokal — **belum offsite**, perlu destinasi cloud/remote yang hanya user yang punya kredensialnya) — REVIEW 🟡 sebagian
- [x] Redirect `/` ke `/admin` (bukan welcome default Laravel) — REVIEW ⚪ selesai

## Tasks — UI (dikerjakan oleh sesi AI, bukan Claude Code)

> Implementasi UI direncanakan & dikerjakan oleh sesi AI pada fase lanjutan (bukan bagian TASKS.md untuk Claude Code).
> Detail desain di `DESIGN.md` §"Desain UI".

- [x] Dashboard: tambah widget "Kehadiran Hari Ini" (sudah hadir / belum hadir / telat / pulang cepat) — widget status device menyusul
- [x] Badge status kehadiran eksplisit (Hadir / Telat / Pulang Cepat / Alpa / Libur) di Rekap Harian & Bulanan
- [x] Rekap Bulanan: matriks pegawai × tanggal (H/T/P/A/L) + legenda (klik sel → detail harian masih menyusul)
- [x] Detail pegawai: kalender bulan berjalan dengan warna status (termasuk hari libur dari tabel `holidays`)
- [x] Konsistensi tema: halaman login, dark mode, halaman error/simple
- [ ] Rapikan pendekatan theming (CSS overlay rapuh): versi-pin, kurangi `!important`, dokumentasikan opsi tema Filament Tailwind penuh

## Roadmap UI (2026-08-26)

Prioritas nilai fungsional > kosmetik. Implementasi oleh sesi AI.

1. **Dashboard informatif** — selain 4 stat card, tambah tabel "Kehadiran Hari Ini" (daftar pegawai + jam masuk + status) dan widget status device.
2. **Badge status** — tampilkan status eksplisit berwarna (Hadir hijau, Telat merah, Pulang Cepat oranye, Alpa abu, Libur abu) di semua rekap, bukan hanya angka menit.
3. **Matriks Rekap Bulanan** — tabel pegawai × tanggal (sel berisi H/T/P/A/L) + ringkasan, klik sel → detail hari itu.
4. **Kalender detail pegawai** — warna status per hari dalam sebulan.
5. **Konsistensi tema** — login, dark mode, halaman error, dan halaman simple memakai token tema yang sama.
6. **Perkuat theming** — keputusan pendekatan: rapikan CSS overlay sekarang (versi-pin, hilangkan `!important` berlebih); evaluasi tema Filament Tailwind penuh (butuh PHP+Node untuk build — server AI punya Node, CloudPanel user `absensi` tidak) dengan commit aset `public/build` terkompilasi.

## Roadmap review (2026-08-26)

Detail lengkap di `REVIEW.md`. Prioritas menurun.

1. **False-positive "pulang cepat"** (`app/Services/AttendanceRecapCalculator.php`): `check_out = logs->last()` tanpa jendela validasi → scan pagi berulang dianggap pulang cepat. Fix: `check_out` hanya valid jika scan terakhir ≥ tengah hari (mis. ≥ 12:00) atau ≥ N jam setelah check_in; selain itu `null`.
2. **Keamanan `/iclock/*`** (`config/zkteco-adms.php`): endpoint publik tanpa token, `auto_register` true. Fix: validasi `SN` (device tak dikenal ditolak/ditandai), opsi shared secret/IP whitelist, log semua request.
3. **Hari libur nasional** (`AttendanceRecapCalculator::recomputeForEmployeeAndDate`): tambah tabel `holidays` + resource admin + seeder; skip tanggal libur.
4. **Export rekap** (Excel/PDF): tombol export di Rekap Bulanan & Rekap Harian.
5. **Scheduler rekonsiliasi**: jadwalkan `RecomputeAttendanceRecaps` di `routes/console.php` (dailyAt 04:00) + cron `schedule:run` di CloudPanel.
6. **Optimasi recompute push batch**: coalesce recompute per (employee_id, date) agar tidak dijalankan ganda dalam satu request `/iclock`.
7. **Timezone**: pastikan `APP_TIMEZONE` konsisten dengan device; verifikasi pengelompokan tanggal.
8. **Test**: unit/feature test untuk `AttendanceRecapCalculator`.
9. **Backup**: cron `mysqldump` DB `absensi` harian ke storage eksternal.
10. **Data pegawai**: lengkapi NIP resmi & jabatan/departemen (employee_code saat ini = PIN).
11. **Rapikan**: redirect `/`, versioning CSS, audit kolom package `AttendanceLog`.

## Log sesi

### 2026-08-26
- Project diinisialisasi dari template.
- Riset protokol ADMS ZKTeco langsung dari source code package Laravel open-source `syofyanzuhad/filament-zkteco-adms` (MIT) untuk memastikan format wire protocol benar.
- Server: CloudPanel di `203.6.149.150`, site `absensi.mipa.uns.ac.id` sudah ada duluan (DNS + SSL Let's Encrypt sudah aktif sebelum sesi ini).
- Scaffold Laravel 12 + Filament 4 di server via SSH (user `absensi`, PHP 8.5), DB MySQL `absensi`.
- Install `syofyanzuhad/filament-zkteco-adms`, migrate, register plugin di `AdminPanelProvider`. Endpoint `/iclock/cdata`, `/iclock/getrequest`, `/iclock/devicecmd` teruji lewat curl simulasi (handshake, push ATTLOG) — data masuk benar ke `zkteco_devices`/`zkteco_attendance_logs`, lalu data uji dihapus.
- Buat model+migration+Filament resource `Employee` (nama, kode/NIP, jabatan, departemen, `zkteco_pin`) dengan relation manager read-only untuk riwayat absensi per pegawai.
- User admin Filament pertama dibuat: email `faavha@gmail.com` (password dikirim ke user langsung, tidak disimpan di sini).
- **Bug ditemukan & diperbaiki**: login admin tidak berfungsi karena vhost Nginx CloudPanel meng-intercept `/livewire/livewire.js` (route dinamis Laravel) sebagai file statis dan mengembalikan 404 mentah sebelum sampai ke PHP-FPM — di kedua server block (edge 80/443 dan backend 8080). Fix: tambah `location = /livewire/livewire.js { ... }` (exact-match, prioritas tertinggi Nginx) di kedua block.
- Device fisik (`SPK7234200330`) berhasil disetel ke Cloud Server Setting → `absensi.mipa.uns.ac.id:443`, otomatis terdaftar & online.
- **Trik ambil data lama dari device**: kirim `DeviceCommand` custom via tinker (`DATA QUERY USERINFO` dan `DATA QUERY ATTLOG`) — device mengambilnya lewat polling `/iclock/getrequest` rutin, lalu push balik semua data historisnya. Hasil: 14 user (nama+PIN) dan 3.263 log absensi (1 Okt 2025 s/d sekarang) berhasil ditarik penuh dari memori device tanpa menghapus apa pun di alat.
- Import otomatis 14 `Employee` dari data `zkteco_users` (employee_code sementara = PIN, perlu diisi NIP resmi belakangan).
- Catatan: PIN `1` punya 2 log tapi tidak ada di data user (kemungkinan akun admin/test device, bukan pegawai) — diabaikan.
- Push ke GitHub: `https://github.com/faavha-alt/absensi` (remote `origin`, branch `master`), pakai deploy key khusus (bukan SSH key pribadi user).
- **Fitur rekap absensi v2 (parsial)**: tabel `work_schedules` (1 baris global: jam masuk, jam pulang, toleransi telat, toleransi pulang cepat, default 08:00–16:00) dan `attendance_recaps` (1 baris per pegawai per hari kerja: check_in, check_out, late_minutes, early_leave_minutes — Sabtu/Minggu tidak dihitung sama sekali, tidak dibuatkan baris rekap).
- `App\Services\AttendanceRecapCalculator`: hitung status 1 hari (dipakai listener) & `recomputeAll()` untuk hitung ulang semua histori (dipanggil otomatis tiap pengaturan jam kerja disimpan). Kalau cuma 1x scan dalam sehari, `check_out` dibiarkan null (tidak dihitung pulang cepat) — hindari false positive "pulang sangat cepat".
- Listener `RecomputeAttendanceRecap` (didaftarkan di `AppServiceProvider::boot()`) dengar event `AttendanceReceived` dari package ADMS → rekap ter-update otomatis real-time tiap device push data baru, tanpa perlu cron.
- Filament: halaman `Pengaturan Jam Kerja` (`/admin/work-schedule-settings`) untuk set jam masuk/pulang & toleransi; resource read-only `Rekap Absensi` (`/admin/attendance-recaps`, per hari) dengan filter bulan/pegawai/"hanya yang telat"; halaman `Rekap Bulanan` (`/admin/monthly-attendance-recap`) menampilkan agregat per pegawai per bulan (jumlah hadir, jumlah telat, total menit telat, jumlah pulang cepat).
- Semua halaman baru diverifikasi render tanpa error via simulasi request ter-autentikasi (tidak ada akses browser langsung di sesi ini).
- Tambah kolom `employees.is_admin` — pegawai dengan privilege ADMS bukan 0 (PIN 11 & 13, sesuai data `zkteco_users.privilege`) ditandai admin device dan **dikecualikan total** dari Rekap Absensi & Rekap Bulanan (`AttendanceRecapCalculator` skip employee `is_admin=true`, hapus recap lama miliknya). Bisa diatur manual lewat toggle "Admin device" di form Employee untuk pegawai lain ke depannya.
- Rekap Bulanan: filter diganti dari date-picker (ada tanggal) jadi 2 dropdown terpisah (Bulan + Tahun saja), sesuai permintaan user.
- **Tema visual**: server tidak punya Node/npm dan user SSH tidak punya sudo (dicek, tidak bisa install) — jadi custom theme Filament (yang biasanya butuh build Tailwind via Vite) **tidak bisa** dipakai. Solusi: custom CSS statis (`public/css/tailadmin-theme.css`, di-inject via `renderHook` di `AdminPanelProvider`, tanpa build step). Widget `AttendanceOverview` (stat cards) di dashboard, kartu statistik di Rekap Bulanan. Catatan: pendekatan "mirip nuansa", BUKAN kloning identik — struktur layout Filament (sidebar, komponen) tidak diubah karena itu perlu menulis ulang hampir semua view Filament dari nol. Kalau user kasih akses root/Node nanti, theming custom Tailwind penuh baru memungkinkan hasil lebih presisi.
- **Iterasi ke-2 tema (ikut referensi AdminKit, https://demo-basic.adminkit.io/)**: diambil token desain asli dari CSS AdminKit via curl (bukan tebakan) — primary `#3b7ddd`, page bg `#f5f7fb`, sidebar navy gelap `#222e3c` dengan border-left aktif biru + gradient tint, card radius kecil `0.25rem` (flat, minim shadow), font Inter (sudah default Filament, tidak perlu load eksternal). Diterapkan di `tailadmin-theme.css` + warna panel di `AdminPanelProvider`.
- **Aturan pulang cepat kalau tidak scan pulang**: sebelumnya kalau cuma 1x scan (tidak ada scan pulang), `early_leave_minutes` dibuat 0 (menghindari false positive). Sekarang diubah sesuai permintaan user: dianggap pulang cepat dengan penalti tetap **maksimal 60 menit** (`AttendanceRecapCalculator::NO_CHECKOUT_EARLY_LEAVE_MINUTES`). Early-leave dari scan pulang asli (bukan karena lupa absen) tetap dihitung apa adanya, tidak dibatasi 60 menit.
- **Eksekusi `TASKS.md`** (hasil review kode dari sesi AI lain, lihat `REVIEW.md`/`DESIGN.md`): 9 dari 10 tugas selesai (1 sebagian, poin data pegawai perlu isi manual oleh user).
  - **TUGAS 1** (fix false-positive pulang cepat): `check_out` sekarang hanya valid kalau scan terakhir ≥ jam 12 siang ATAU ≥4 jam dari check-in; selain itu dianggap tidak ada checkout valid (masuk ke jalur flat 60 menit dari fix sebelumnya). Scan ulang pagi hari tidak lagi salah-tangkap sebagai "pulang cepat 6 jam".
  - **TUGAS 7** (timezone): `config/app.php` timezone diubah dari hardcode `UTC` ke `env('APP_TIMEZONE', 'Asia/Jakarta')`, `.env` diisi `APP_TIMEZONE=Asia/Jakarta`. Penting untuk `Carbon::today()`/`now()` di widget dashboard & cron dailyAt supaya sesuai kalender WIB, bukan UTC.
  - **TUGAS 2** (keamanan `/iclock/*`): `auto_register` dimatikan (device asli sudah terdaftar duluan, jadi tidak kena dampak); device/SN baru sekarang ditolak (`ERROR`, tidak masuk DB) — dites dengan SN palsu. Tambah middleware `LogAndFilterAdmsRequest` (channel log `adms`, harian, retensi 30 hari) mencatat tiap request (SN, IP, path, body preview) untuk audit, plus opsi `ZKTECO_ALLOWED_IPS` di `.env` untuk whitelist IP (belum diaktifkan by default karena IP kantor bisa saja dinamis — aktifkan manual kalau sudah pasti IP publik kantor statis).
  - **TUGAS 3** (hari libur nasional): tabel+model+Filament resource `Holiday`, seeder 25 tanggal libur nasional & cuti bersama 2026 (data SKB 3 Menteri, dicek via web search, bukan tebakan). `AttendanceRecapCalculator` skip tanggal yang ada di `holidays`.
  - **TUGAS 4** (export): Rekap Absensi (harian) pakai `Filament\Actions\ExportAction` native (nulis .xlsx asli via `openspout`, sudah jadi dependency Filament) + `AttendanceRecapExporter`. Rekap Bulanan pakai tombol "Export CSV" (streamDownload, tidak butuh dependency tambahan). **Catatan**: export harian butuh sistem antrian (Filament export selalu queued) — `QUEUE_CONNECTION` diubah dari `database` ke `sync` (server tidak punya proses worker/supervisor, dan tidak ada akses root untuk pasang; `sync` menjalankan job langsung inline, cocok untuk skala kecil ini).
  - **TUGAS 5** (scheduler): `routes/console.php` daftarkan `Schedule::command('attendance:recompute-recaps')->dailyAt('04:00')`. Crontab user `absensi` dipasang (`crontab -l` kosong sebelumnya, ternyata bisa dipasang tanpa root): `* * * * * php artisan schedule:run` tiap menit.
  - **TUGAS 6** (optimasi recompute batch): listener `RecomputeAttendanceRecap` coalesce — simpan pasangan (employee_id,tanggal) yang sudah direcompute dalam request yang sama (static array), skip kalau sudah pernah, supaya push batch banyak log tidak recompute berulang untuk tanggal yang sama.
  - **TUGAS 8** (test otomatis): `tests/Feature/AttendanceRecapCalculatorTest.php`, 9 test mencakup semua skenario di REVIEW.md (hadir, telat dgn/tanpa toleransi, pulang cepat valid, false-positive scan pagi, single-scan, skip weekend, skip libur, skip admin). `tests/Feature/ExampleTest.php` diupdate (assert redirect `/` → `/admin`, bukan status 200 welcome page lama). Semua 11 test hijau (`php artisan test`), pakai sqlite in-memory (aman, tidak sentuh MySQL produksi).
  - **TUGAS 9** (backup): script `/home/absensi/scripts/backup-db.sh` (pakai `clpctl db:export`, hasil ke `/home/absensi/backups/databases/`, retensi 14 hari). Cron harian jam 02:00. Diverifikasi: gzip integrity OK, isi dump ada 17 tabel yang benar. **Belum offsite** — cuma lokal di server yang sama; untuk offsite butuh destinasi cloud/remote (S3, rclone, dst) yang kredensialnya cuma user yang punya.
  - **TUGAS 10** (rapikan): `/` redirect ke `/admin` (bukan welcome Laravel), CSS theme versioning pakai `filemtime()` (bukan `?v=5` hardcode manual). NIP resmi/jabatan/departemen pegawai **belum diisi** — perlu data asli dari user, tidak bisa dikarang.
  - Dijalankan `./vendor/bin/pint` di akhir (13 style issue diperbaiki otomatis, tidak ada perubahan logika).
