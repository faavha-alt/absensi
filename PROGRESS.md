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
- [x] Export rekap (Excel via Filament ExportAction di Rekap Absensi; Rekap Bulanan awalnya CSV, lalu diupgrade ke Excel/.xlsx formal via OpenSpout — commit `857f47a`, belum sempat tercatat di sini sampai audit 2026-08-26)
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
- [x] Bangun tema Filament asli (Tailwind) `resources/css/filament/admin/theme.css` + registrasi `->viteTheme()` — ganti pendekatan CSS-overlay. `public/css/tailadmin-theme.css` kini hanya utilitas custom (stat card, badge, matriks, kalender). Aset di-build di server AI (Node tersedia) → `public/build` di-deploy via scp ke CloudPanel (server produksi tidak perlu Node).
- [x] Iterasi tema lanjutan "Soft Modern Light" (sidebar putih, aksen indigo `#4f46e5`, gaya terinspirasi Linear/Stripe) — menggantikan "Clean Modern Light Admin" (sidebar navy) — commit `2bf898e`, belum sempat tercatat di sini sampai audit 2026-08-26.

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
- **Verifikasi konsistensi**: ditemukan commit UI dari sesi AI lain (`b58bf48`..`1350afd`, widget "Kehadiran Hari Ini", badge status, matriks Rekap Bulanan, kalender pegawai, tema "Clean Modern Light Admin") sudah masuk git log & sudah ter-deploy ke server yang sama — dicek via `diff -rq` antara pull server vs repo lokal, hasilnya identik (tidak ada drift). Semua 11 test tetap hijau sebelum lanjut kerja.
- **Manajemen admin (login & ganti password)**: aktifkan `->profile()` bawaan Filament (`/admin/profile`, form ganti nama/email/password sendiri). Tambah Filament Resource `User` (`/admin/users`, grup nav "Pengaturan") untuk kelola akun admin lain — create/edit/hapus. Password di-hash otomatis (`dehydrateStateUsing` + `Hash::make`), dikosongkan saat edit = tidak ganti password (`dehydrated` skip kalau kosong). Tambah `UserPolicy`: admin tidak bisa hapus akun sendiri (cegah lockout), tapi bisa hapus admin lain. 5 test baru (`UserManagementTest`) memverifikasi lewat Livewire test asli (bukan cuma render halaman): create dengan password ter-hash, edit tanpa isi password tidak ganti password lama, edit dengan isi password mengganti password, tidak bisa hapus diri sendiri, bisa hapus admin lain. Total 16 test hijau.
- **Bug report user: "view di employee error"** — ditemukan 2 bug dari perubahan UI sesi lain di `EmployeeResource::infolist()`: (1) `Filament\Infolists\Components\Section` tidak ada di Filament 4, harusnya `Filament\Schemas\Components\Section`; (2) `TextEntry` tidak punya method `->boolean()`, harus pakai `IconEntry` untuk field boolean (`is_admin`). Cara diagnosis: cek `storage/logs/laravel.log` langsung (respons HTTP 500 di production tidak menampilkan detail error). Setelah fix, sweep render 27 halaman admin (semua resource + record CRUD) untuk cari bug serupa lainnya — tidak ada, semua 200. 16 test tetap hijau. Juga rapikan: `public/css/filament`, `public/js`, `public/fonts` (asset publish Filament) sempat ke-track git tidak sengaja saat pull dari server — dihapus dari repo & ditambahkan ke `.gitignore`.

### 2026-08-26 (audit dokumentasi)
- **Audit dokumentasi** (sesi terpisah, setelah commit `aee2e96`) — tugas: cek `PROGRESS.md` vs `git log`, verifikasi checklist `- [ ]` yang mungkin sudah selesai, cari bug/gap tersembunyi. Tidak ada perubahan kode, hanya `PROGRESS.md`.
- `git log --oneline -40` dibandingkan baris demi baris dengan checklist & Log sesi di atas. Ditemukan 2 commit UI yang sudah masuk git & sudah dijalankan tapi belum tercatat di sini (sudah ditambahkan ke checklist): `857f47a` (Rekap Bulanan: CSV → Excel/.xlsx formal via OpenSpout, `app/Services/MonthlyRecapExcelBuilder.php`) dan `2bf898e` (tema "Soft Modern Light" — sidebar putih, aksen indigo `#4f46e5` — menggantikan "Clean Modern Light Admin" sidebar navy). Juga ada `a4211eb`, fix minor tipe properti `$sort` widget (`int|string` → `?int` sesuai base class Filament) — tidak berdampak fungsional, dicatat di sini saja tanpa entri checklist tersendiri.
- Semua item `- [x]` di checklist utama diverifikasi ulang ke kode (bukan cuma dipercaya dari histori): `AttendanceRecapCalculator::resolveValidCheckOut()` & konstanta `CHECKOUT_MIN_HOUR`/`CHECKOUT_MIN_HOURS_AFTER_CHECKIN` sesuai klaim TUGAS 1; `app/Http/Middleware/LogAndFilterAdmsRequest.php` + `config('zkteco-adms.security.allowed_ips')` sesuai klaim TUGAS 2 (auto_register off, IP allowlist opsional, log ke channel `adms`); `HolidaySeeder` berisi 25 tanggal 2026 sesuai klaim; scheduler `attendance:recompute-recaps` dailyAt 04:00 terdaftar di `routes/console.php`; status kehadiran (Hadir/Telat/Pulang Cepat/Alpa/Libur) dihitung dari satu sumber `AttendanceRecap::status()` yang dipakai konsisten di semua widget/halaman (`TodayAttendance`, `AttendanceRecapsTable`, `MonthlyAttendanceRecap::getMatrix()`, `ViewEmployee::getCalendar()`) — tidak ada logika status yang terduplikasi/berisiko drift antar tempat.
- 2 item `- [ ]` yang tersisa ("Lengkapi kode/NIP resmi & jabatan/departemen" dan "Jadwal kerja per pegawai/departemen v3") dicek ke kode: memang belum ada implementasinya (tidak ada mekanisme isi NIP otomatis, `WorkSchedule::current()` masih single-row global tanpa relasi per-pegawai/departemen) — status "belum" di checklist memang akurat, bukan stale.
- `php artisan test` dijalankan ulang: 16/16 test hijau, 34 assertion — sesuai klaim di Log sesi sebelumnya.
- Grep `TODO|FIXME|XXX` di `app/`, `config/`, `routes/`, `database/`: nihil.
- Dicek `EmployeeResource` dan infolist lain (`AttendanceRecapInfolist`, `UserInfolist`) untuk memastikan tidak ada sisa pemakaian namespace lama `Filament\Infolists\Components\Section` atau `TextEntry::make(...)->boolean()` di luar yang sudah diperbaiki sesi sebelumnya — bersih, hanya `IconEntry` yang pakai `->boolean()`.
- **Terverifikasi 2026-08-26** (SSH langsung ke `absensi-web`, `htdocs/absensi.mipa.uns.ac.id/.env`): `QUEUE_CONNECTION=sync` ✅ dan `APP_TIMEZONE=Asia/Jakarta` ✅ — sesuai klaim TUGAS 4 & TUGAS 7. Export Excel aman, job jalan inline tanpa butuh worker. `.env` lokal di `/ai/projects/absensi` (dev/test) yang beda nilai memang tidak relevan, seperti dugaan semula.
- **Temuan awal 2026-08-26**: `.env` produksi punya `APP_ENV=local` dan **`APP_DEBUG=true`** — kombinasi berbahaya: error apapun di domain live `absensi.mipa.uns.ac.id` akan menampilkan halaman debug Laravel lengkap ke publik (stack trace, env var termasuk kredensial DB, query SQL).
- **Sempat diubah ke `APP_DEBUG=false` + `APP_ENV=production`, lalu DI-REVERT total di hari yang sama** setelah user melaporkan `GET /admin` mulai kena `403 Forbidden` dari jaringan kampus (`203.6.148.170`) beberapa menit setelah perubahan `APP_ENV`. Investigasi saat itu:
  - Test langsung dari server (bypass TLS/edge, langsung ke backend port 8080 dengan Host header manual) selalu dapat `302` normal, tidak pernah `403` — tidak bisa direproduksi dari dalam.
  - Kode aplikasi dicek: tidak ada middleware IP-allowlist untuk route `/admin`, tidak ada logic yang bercabang berdasar `environment('local')`/`APP_ENV`, `session.secure` baca env var terpisah (`SESSION_SECURE_COOKIE`) bukan dari `APP_ENV`.
  - Varnish (`/etc/varnish/default.vcl`) dicek lengkap: tidak ada aturan yang mengembalikan 403 untuk `/admin`, cuma bypass cache.
  - **Akar masalah tidak berhasil dipastikan** — dugaan kuat ada lapisan WAF/ModSecurity/brute-force-protection CloudPanel di luar akses SSH user `absensi` (bukan root, tidak bisa baca config nginx utama) yang bereaksi terhadap perubahan `APP_ENV`, atau setidaknya berkorelasi waktu sangat dekat (403 muncul ~3 menit setelah perubahan). Tidak sempat diverifikasi lebih jauh — user minta segera direvert.
  - **Direvert 2026-08-26**: `APP_ENV` → `local`, `APP_DEBUG` → `true` (balik ke kondisi persis semula). Diverifikasi via `php artisan tinker` dan curl port 8080 (`302` normal) — **user konfirmasi dari browser asli: sudah normal lagi.**
- **Status akhir: security fix ini DIBATALKAN untuk sementara.** Celah `APP_DEBUG=true` di produksi masih terbuka. Kalau mau dicoba lagi: ubah satu variabel dulu (misal `APP_DEBUG` saja tanpa `APP_ENV`) untuk isolasi penyebab, dan/atau cek dulu pengaturan security/WAF di dashboard CloudPanel (akses yang tidak dimiliki sesi SSH ini) sebelum coba lagi.

### 2026-09-24 (sesi UI/laporan)

- **Fix 404 `/livewire/livewire.js`**: Nginx meng-intercept path itu sebagai file statis. Solusi tanpa akses `/etc/nginx` (user `absensi` bukan root): salin `vendor/livewire/livewire/dist/livewire.js` → `public/livewire/livewire.js`. Terverifikasi HTTP 200 (sebelumnya 404 → panel Livewire rusak).
- **Fix data identik Imam & Sukoco**: penyebab = mapping `zkteco_pin` sempat salah/duplikat; record `employees` diperbaiki 2026-09-24 17:36–17:38 tapi rekap belum dihitung ulang. Dijalankan `attendance:recompute-recaps` (`recomputeAll()`, 1.589 hari-pegawai). Cek menyeluruh: tidak ada lagi rekap/log/PIN/NIP duplikat.
- **Fitur laporan performa** — 2 halaman Filament baru: `/admin/employee-performance` (per pegawai) & `/admin/yearly-recap` (agregat 12 bulan + ranking pegawai). Chart.js self-hosted (`public/js/chart.umd.min.js`, dimuat via renderHook `HEAD_END` di `AdminPanelProvider`). Service `App\Services\AttendancePerformanceService` (`monthlyForEmployee`, `monthlyTotals`, `yearlyByEmployee`, `availableYears`). Komponen blade reusable `resources/views/components/attendance-chart.blade.php` (Alpine + `wire:key` supaya chart redraw saat filter berubah). Terverifikasi via tinker: data & konfigurasi grafik benar (12 bulan, 8 pegawai, total hadir 2024=1204); **render visual belum dicek di browser**.
- **Jebakan penting**: property bertipe di child class Filament harus **persis sama** dengan base-nya, kalau tidak → PHP fatal saat class di-load. Contoh: `Widget::$sort` = `?int` (bukan `int|string`), `$navigationGroup` = `string|UnitEnum|null` + butuh `use UnitEnum;`.
- **Handoff / langkah berikutnya**: (1) verifikasi visual grafik di browser pada 2 halaman baru itu; (2) pertimbangkan tombol "Hitung ulang rekap" di panel agar tak perlu command manual; (3) sisa item lama: isi NIP resmi/jabatan, jadwal kerja per departemen; (4) repo lokal & server belum sepenuhnya sinkron (server bukan git repo; deploy via scp).

### 2026-09-25 (RESET UI → AeroDeck: F0 + F1 SELESAI)

- **Keputusan user**: hapus Filament total; tata ulang UI/UX saja (data & backend aman); mulai dari F0+F1. Rencana lengkap di **`PLAN.md`**.
- **F0 fondasi AeroDeck** (gaya glassmorphism Apple/Tesla dari skill `ui-dashboard-aerodeck`):
  - Layout: `resources/views/layouts/{app,sidebar,header}.blade.php` (sidebar floating, header glass).
  - Komponen: `resources/views/components/app-icon.blade.php` (⚠ JANGAN pakai nama `x-icon` — bentrok dengan blade-icons Filament).
  - CSS token & komponen di `resources/css/app.css` (`.apple-glass-card`, `.apple-interactive-card`, `.font-mono-num`, `.badge-status`, `.cal-cell`).
  - Font self-host via Vite/bunny: **Plus Jakarta Sans** + **JetBrains Mono** (mengganti Instrument Sans) di `vite.config.js`.
  - Auth custom: `AuthController` + `resources/views/auth/login.blade.php`; route `/login`, `/logout`.
- **F1 Dashboard**: `DashboardController` + `resources/views/dashboard/index.blade.php` — KPI bento (pegawai aktif, hadir, telat, belum absen + status device), grafik tren 7/30/90 hari (Chart.js), feed scan terbaru, tabel kehadiran hari ini dengan badge status.
- **Filament DIHAPUS**: `app/Filament/`, `app/Providers/Filament/`, `resources/views/filament/`, `resources/css/filament/` dihapus (lokal + server); `AdminPanelProvider` dicabut dari `bootstrap/providers.php`. **Endpoint alat `/iclock/*` TETAP JALAN** (didaftarkan package `syofyanzuhad/filament-zkteco-adms` secara independen — package tetap terpasang karena dipakai model/event-nya).
- **Navigasi**: 11 menu kelompok (Pantau/Kepegawaian/Rekap/Analitik/Pengaturan) sudah tampil; modul belum dibangun diarahkan ke halaman placeholder `resources/views/soon.blade.php` + label "soon".
- **Verifikasi (di server)**: `php -l` lolos; `login=200`, `/admin=404` (Filament hilang), `/iclock/cdata=200` (alat aman); dashboard render 47 KB via tinker (chart+KPI+tabel ada); `app.css` 57 KB ter-serve.
- **Handoff / langkah berikutnya (F2 dst. sesuai PLAN.md §7)**: F2 Data Pegawai (daftar+detail, kalender, performa) → F3 Rekap Harian/Bulanan + export → F4 Analitik (Performa & Tahunan — logika service `AttendancePerformanceService` & `MonthlyRecapExcelBuilder` sudah ada, tinggal dibungkus UI) → F5 Pengaturan (Jam Kerja, Libur, Akun, Perangkat) → F6 poles. Catatan: halaman pengaturan perlu tulis-endpoint (recompute otomatis) yang dulu ada di Filament.
- **Jebakan baru**: `<x-icon>` bentrok dengan blade-icons (Filament) → fatal "Svg by name ... not found"; gunakan nama unik (`x-app-icon`).
