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
- [ ] v2: aturan jam kerja, status telat/pulang cepat/alpa, rekap bulanan/export

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
