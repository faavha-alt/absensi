# Desain Sistem Absensi Pegawai

> Ruang lingkup: **manajemen absensi/kehadiran pegawai saja**.
> Perhitungan gaji/upah dan manajemen kontrak sengaja DI LUAR lingkup (diproses terpisah di project lain).
> Dokumen ini adalah panduan desain; kondisi implementasi aktual dicatat di `PROGRESS.md`.

## 1. Tujuan

Merekam kehadiran pegawai dari alat absensi (fingerprint/face ZKTeco) secara otomatis, lalu
menyajikan **rekap kehadiran harian & bulanan** yang akurat untuk keperluan administrasi kepegawaian.
Semua proses otomatis: pegawai scan di alat → data masuk ke server → rekap ter-update real-time,
tanpa input manual.

## 2. Arsitektur & Alur Data

```
┌─────────────────┐   push (polling /iclock/*)   ┌──────────────────────────┐
│  Alat ZKTeco     │ ───────────────────────────► │  Server (Laravel 12)      │
│  MB20            │   (set Cloud Server Setting) │  package zkteco-adms      │
└─────────────────┘                               └────────────┬─────────────┘
                                                               │ simpan mentah
                                                               ▼
                                             zkteco_devices / zkteco_attendance_logs
                                                               │
                                                               ▼
                                             AttendanceRecapCalculator
                                             (rekap per pegawai per hari)
                                                               │
                                                               ▼
                                             attendance_recaps (hadir/telat/pulang cepat)
                                                               │
                                           ┌───────────────────┴───────────────────┐
                                           ▼                                       ▼
                                   Admin Filament (web)                 Rekap Bulanan agregat
                                   Dashboard + resource                  per pegawai per bulan
```

**Kunci desain:**
- **Data mentah tidak diubah.** Log mentah dari alat (`zkteco_attendance_logs`) disimpan apa adanya.
  Rekap (`attendance_recaps`) adalah hasil perhitungan turunan yang bisa dihitung ulang kapan pun
  (`AttendanceRecapCalculator::recomputeAll()`) tanpa merusak data asli.
- **Real-time via event.** Tiap scan baru yang di-push alat memicu event `AttendanceReceived` →
  listener menghitung ulang rekap hari itu, tanpa perlu cron/jadwal.
- **Hari kerja = Senin–Jumat.** Sabtu & Minggu tidak dibuatkan rekap.
- **Admin device dikecualikan.** Pegawai yang bertugas sebagai admin alat (privilege ADMS ≠ 0)
  tidak dihitung kehadirannya, dan bisa diatur manual lewat toggle "Admin device" di data pegawai.

## 3. Aturan Bisnis Kehadiran

Dengan **jam kerja global** (default 08:00–16:00) dan toleransi:

- **Hadir** → ada minimal 1 scan pada hari kerja.
  - `check_in` = scan pertama hari itu.
  - `check_out` = scan terakhir hari itu (hanya jika scan > 1; jika cuma 1x scan, `check_out` dibiarkan kosong
    supaya tidak salah-menandai "pulang cepat").
- **Telat** → `check_in` melewati `jam_masuk + toleransi_telat`. Besar keterlambatan disimpan sebagai
  `late_minutes`.
- **Pulang cepat** → `check_out` lebih awal dari `jam_pulang − toleransi_pulang_cepat`. Disimpan
  sebagai `early_leave_minutes`.

Tabel `work_schedules` (saat ini 1 jadwal global) bisa dikembangkan menjadi per departemen/per pegawai.

## 4. Modul & Halaman (Admin Filament)

| Modul | Halaman | Status |
|---|---|---|
| Dashboard | Stat cards: Total Pegawai, Hadir Hari Ini, Telat Hari Ini, Device Online | ✅ Ada |
| Data Pegawai | CRUD `Employee` (nama, kode/NIP, jabatan, departemen, PIN ZKTeco, admin) + riwayat absensi per pegawai | ✅ Ada |
| Jam Kerja | `Pengaturan Jam Kerja` (jam masuk, pulang, toleransi) | ✅ Ada |
| Rekap Harian | `Rekap Absensi` per hari + filter (bulan/pegawai/hanya telat) | ✅ Ada |
| Rekap Bulanan | `Rekap Bulanan` agregat per pegawai per bulan (hadir, telat, menit telat, pulang cepat) | ✅ Ada |
| Export rekap | Unduh rekap bulanan/harian sebagai Excel/PDF | 🔜 Direncanakan |
| Jadwal per departemen | Jam kerja berbeda per departemen/pegawai | 🔜 Direncanakan (v3) |

## 5. Data Model

- **employees** — `name`, `employee_code` (NIP), `position` (jabatan), `department`, `zkteco_pin`, `is_admin`.
- **work_schedules** — `time_in`, `time_out`, `late_tolerance_minutes`, `early_leave_tolerance_minutes`.
- **attendance_recaps** — `employee_id`, `date`, `check_in`, `check_out`, `late_minutes`, `early_leave_minutes`.
- **zkteco_devices / zkteco_attendance_logs / zkteco_users** — data mentah dari alat (dikelola package ADMS).

## 6. Item yang Belum Selesai (lingkup absensi)

1. **Export rekap** (Excel/PDF) untuk Rekap Harian & Rekap Bulanan.
2. **Jadwal kerja per departemen/pegawai** (saat ini 1 jadwal global) — hanya jika kebutuhan berubah.
3. **Lengkapi data pegawai**: isi NIP resmi & jabatan/departemen (saat ini `employee_code` masih = PIN).

## 7. Keputusan yang DIPAKAI saat ini (scope)

- Hanya **manajemen absensi** — tanpa perhitungan gaji/upah dan tanpa manajemen kontrak.
- Rekap kehadiran adalah keluaran utama; data pendukung upah diserahkan ke sistem/process lain.

## 8. Desain UI

> Status: **direncanakan**, implementasi oleh sesi AI (fase lanjutan). Checklist & roadmap di `PROGRESS.md`.
> Pendekatan saat ini: tema overlay CSS (`public/css/tailadmin-theme.css`) di atas komponen Filament (AdminKit style), karena server produksi tidak punya Node untuk build tema Tailwind penuh.

### Arah desain (keputusan — disetujui 2026-08-26)

**"Clean Modern Light Admin" (gaya SaaS modern).** Bukan flat AdminKit, tapi refinement ke tampilan lebih segar & modern — tetap light untuk penggunaan kantor/harian.

- Background putih/abu-putih lembut + banyak whitespace.
- **Sidebar dark navy** (`#222e3c`) dipertahankan sebagai identitas & kontras, disempurnakan (grup menu jelas, ikon konsisten).
- **Kartu**: border halus + radius sedang + shadow sangat tipis (bukan flat 0.25rem); stat card dengan ikon & hierarki jelas.
- **Typografi**: Inter, angka besar untuk KPI.
- **Warna status kehadiran** konsisten: Hadir `#2fb380`, Telat `#dc3545`, Pulang Cepat `#ffc107`, Alpa `#6c757d`, Libur `#adb5bd`.
- **Dark mode**: didukung penuh (opsional; light tetap default).

### Prinsip
- **Nilai fungsional dulu, kosmetik belakangan.** Tampilan harus memudahkan admin menjawab cepat: *siapa hadir hari ini, siapa telat, siapa belum hadir*.
- **Status eksplisit & konsisten.** Selalu tampilkan badge status berwarna, bukan hanya angka menit.
- **Token desain terpusat** (variabel CSS `--ak-*`): warna primary `#3b7ddd`, page bg `#f5f7fb`, sidebar navy `#222e3c`, shadow halus.

### Layar & perbaikan
1. **Dashboard** — stat cards + widget baru:
   - "Kehadiran Hari Ini": tabel pegawai + jam masuk + status (Hadir/Telat/Belum hadir).
   - Widget status device (online/offline, SN, last sync).
2. **Badge status** (di Rekap Harian & Bulanan):
   - Hadir `#2fb380`, Telat `#dc3545`, Pulang Cepat `#ffc107`, Alpa `#6c757d`, Libur `#adb5bd`.
3. **Rekap Bulanan matriks** — tabel `pegawai × tanggal`, sel berisi inisial status (H/T/P/A/L) berwarna; ringkasan per pegawai; klik sel → detail hari itu.
4. **Detail pegawai** — kalender bulan berjalan, warna status per hari.
5. **Konsistensi** — login page, dark mode, halaman error/simple memakai token yang sama.

### Pendekatan theming (keputusan)
- **Utamakan theming asli Filament 4** (colors config + custom CSS yang rapi) sebagai fondasi — bukan overlay `!important` berlebihan yang rapuh. Cara ini didukung resmi dan tahan-update ([dev.to: Understanding Filament themes v4/v5](https://dev.to/filamentmastery/understanding-filament-themes-in-v4v5-from-colors-to-custom-css-57a5)).
- **Jangka pendek**: pertahankan custom CSS tapi rapikan — versi-pin (`?v=`), kurangi `!important`, komentari tiap blok, hindari selector class yang berubah antar-versi Filament.
- **Jangka menengah**: evaluasi tema Filament Tailwind penuh. Catatan: server AI ini punya **Node v24**, CloudPanel user `absensi` **tidak**. Build aset Vite bisa dilakukan di server AI lalu commit `public/build` terkompilasi, asalkan ada PHP untuk kompilasi blade pada build. Kalau akses PHP tersedia, ini jalur yang lebih presisi & tahan-update.
