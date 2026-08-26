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
- [ ] Export rekap (Excel/PDF)

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
