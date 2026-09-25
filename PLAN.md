# Rencana Tata Ulang Sistem Absensi Pegawai (Profesional + AeroDeck UI)

> Status: **RENCANA — menunggu persetujuan**. Menggantikan arah desain di `DESIGN.md` §8.
> Prinsip: **backend & data dipertahankan, lapisan UI/UX ditata ulang total**.
> **Spesifikasi matang** (menu, RBAC, tiap layar, design system, metrik, non-fungsional) ada di **`SPEC.md`**.
> Progres: **F0 + F1 sudah selesai & ter-deploy** (lihat `PROGRESS.md`).

---

## 1. Keputusan arsitektur (inti "reset")

Masalah saat ini: UI dibangun di atas Filament. Filament bagus untuk CRUD cepat, tetapi **sulit dibentuk
menjadi tampilan premium ala AeroDeck** (layout floating, glass card, bento KPI). Akibatnya aplikasi
"jalan tapi tidak rapi/kurang informatif".

**Keputusan yang diusulkan:**

| Lapisan | Keputusan |
| :- | :- |
| Data & logika (MySQL, models, services, integrasi ZKTeco ADMS) | **DIPERTAHANKAN** — sudah teruji, jangan dirombak |
| UI utama (dashboard, kehadiran, rekap, laporan, pengaturan) | **DIBANGUN ULANG** sebagai UI custom: Blade + Tailwind (Vite lokal) + Alpine + Chart.js, gaya **AeroDeck** |
| Filament | **Diturunkan** menjadi "Area Admin Teknis" (opsional, di `/admin`): kelola device ZKTeco, user device, log mentah. UI AeroDeck jadi aplikasi utama di `/` |
| Auth | Pakai tabel `users` yang ada; halaman login custom AeroDeck |

Alasan: UI custom memberi kontrol penuh atas tata letak & informasi, tanpa menyentuh data produksi.
**Tidak ada data yang dihapus.**

---

## 2. Aset yang dipakai ulang (inventaris nyata)

- **Models**: `Employee`, `AttendanceRecap`, `WorkSchedule`, `Holiday`, `User`
- **Services**: `AttendanceRecapCalculator` (telat/pulang cepat/libur/admin), `AttendancePerformanceService` (bulanan, agregat, ranking), `MonthlyRecapExcelBuilder`
- **Integrasi**: `syofyanzuhad/filament-zkteco-adms` (device, attendance_logs, users, device_commands) + middleware audit `LogAndFilterAdmsRequest`
- **Command**: `attendance:recompute-recaps` (sudah terjadwal 04:00)
- **Ekspor**: `AttendanceRecapExporter` + Rekap Bulanan Excel
- **Aset UI**: `public/js/chart.umd.min.js` (Chart.js), tema Filament (`resources/css/filament/admin/theme.css`)
- **Data**: ±3.2rb log absensi, 10+ pegawai, rekap s/d 2026-09

---

## 3. Information Architecture (modul & halaman)

Kelompok navigasi sidebar:

### A. Pantau (Monitoring)
1. **Dashboard** — KPI bento (pegawai aktif, hadir hari ini, telat, belum absen), grafik tren kehadiran
   7/30 hari, feed scan terbaru, status device.
2. **Kehadiran Hari Ini** — papan real-time: sudah masuk / telat / belum absen / sudah pulang;
   jam masuk & pulang; pencarian cepat.
3. **Perangkat** — status MB20 (online/offline, last sync, SN), jumlah scan hari ini.

### B. Data Pegawai
4. **Daftar Pegawai** — tabel + filter (departemen, status, admin device) + cari.
5. **Detail Pegawai** — profil, kartu KPI pribadi, **kalender kehadiran bulanan**, grafik performa,
   riwayat scan mentah, tombol edit mapping PIN.

### C. Rekap
6. **Rekap Harian** — per hari, filter (tanggal/pegawai/hanya telat), export Excel.
7. **Rekap Bulanan** — matriks pegawai × tanggal (H/T/P/A/L) + ringkasan + export Excel.
8. **Rekap Tahunan** — agregat 12 bulan + peringkat pegawai (sudah ada logikanya).

### D. Analitik
9. **Performa Pegawai** — grafik bulanan per pegawai (hadir/telat/pulang cepat/menit telat) + KPI.
10. **Analitik Kehadiran** — tren organisasi, jam datang rata-rata, distribusi keterlambatan.

### E. Pengaturan
11. **Jam Kerja & Toleransi**, 12. **Hari Libur**, 13. **Akun Admin**, 14. **Keamanan endpoint** (SN/token/IP).

---

## 4. UX Flow kunci

- **Login** → Dashboard (ringkasan hari ini langsung terlihat).
- **Pantau harian**: Dashboard → "Kehadiran Hari Ini" → klik pegawai → Detail Pegawai.
- **Rekap bulanan**: Rekap Bulanan → pilih bulan → klik sel tanggal → detail hari itu → Export.
- **Analisis**: Performa Pegawai → pilih pegawai + tahun → baca grafik & KPI → Export.
- **Pemeliharaan**: Pengaturan → Jam Kerja → Simpan (otomatis recompute rekap).

---

## 5. Design System — AeroDeck

Ambil dari `references/` skill `ui-dashboard-aerodeck` (jangan mengarang gaya baru):

- **Warna**: background `#F4F4F6`; teks slate-900/400/500; aksen **indigo `#4F46E5`**;
  status emerald (ok), amber (warning), rose (error).
- **Kartu glass**: `.apple-glass-card` → `rgba(255,255,255,0.88)`, `backdrop-filter: blur(24px)`,
  border `rgba(255,255,255,0.7)`, shadow lembut + `inset 0 1px 0` putih; hover `.apple-interactive-card` (`translateY(-2px)`).
- **Tipografi**: `Plus Jakarta Sans` (UI) + `JetBrains Mono` (angka, kelas `font-mono-num`) — **self-host**, jangan CDN.
- **Layout**: sidebar floating kiri (w-64, `rounded-3xl`) → header glass (`h-16`) → konten scrollable `space-y-5`.
- **Grid**: KPI `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`; konten `grid lg:grid-cols-12` (chart 8 / panel 4); tabel full-width.
- **Komponen**:
  - `x-ui.kpi-card` (label, nilai besar mono, delta badge)
  - `x-ui.glass-card` (wrapper umum)
  - `x-ui.chart-card` (judul + rentang 7H/30H/1Y + canvas)
  - `x-ui.table-card` (header + badge + aksi)
  - `x-ui.badge-status` (Hadir/Telat/Pulang Cepat/Alpa/Libur) — pakai `AttendanceRecap::status()`
  - `x-ui.empty-state`, `x-ui.filter-bar`

**Aturan teknis**: Tailwind + Vite **lokal** (tanpa `cdn.tailwindcss.com`); Chart.js sudah self-host;
Plus Jakarta Sans & JetBrains Mono di-download ke `public/fonts` (server produksi tak punya Node →
aset di-build di server AI lalu `public/build` di-deploy, pola yang sudah dipakai).

---

## 6. Wireframe ringkas per layar utama

**Dashboard**
```
[sidebar] [ header: cari + status device + aksi cepat ]
          [KPI][KPI][KPI][KPI]                      <- 4 kartu bento
          [ grafik tren kehadiran 7/30 hari (8) ][ feed scan terbaru (4) ]
          [ tabel ringkas: kehadiran hari ini (12) ]
```

**Kehadiran Hari Ini**
```
[filter: tanggal | departemen | status]  [Export]
[chip ringkas: Hadir n · Telat n · Belum n]
[tabel: Pegawai | Masuk | Pulang | Status(badge) | Aksi]
```

**Detail Pegawai**
```
[header profil + badge status]
[KPI pribadi x4]
[kalender kehadiran (8)] [ringkasan performa mini (4)]
[tabel riwayat scan mentah]
```

**Rekap Bulanan**
```
[filter bulan/tahun] [Export]
[KPI ringkas]
[matriks pegawai x tanggal (H/T/P/A/L, legenda warna)]
```

---

## 7. Roadmap bertahap (aman, tanpa downtime data)

| Fase | Isi | Hasil terlihat |
| :- | :- | :- |
| **F0** | Fondasi: layout AeroDeck (sidebar/header), token CSS, font, komponen dasar, halaman login custom, auth `users` | Kerangka aplikasi hidup di `/` |
| **F1** | Dashboard + Kehadiran Hari Ini | Halaman utama informatif |
| **F2** | Pegawai: daftar + detail (kalender, performa) | Manajemen pegawai |
| **F3** | Rekap Harian + Bulanan (matriks) + export Excel | Pelaporan inti |
| **F4** | Performa Pegawai + Rekap Tahunan + Analitik | Laporan analitik |
| **F5** | Pengaturan: Jam Kerja, Hari Libur, Akun, Perangkat | Kontrol penuh |
| **F6** | Poles (motion, responsif, aksesibilitas), QA seluruh halaman | Siap produksi |

Tiap fase: build lokal → deploy `public/build` + blade → verifikasi di server → commit + catat `PROGRESS.md`.

---

## 8. Keputusan yang perlu persetujuan Anda

1. **Filament**: dipertahankan sebagai "Area Admin Teknis" di `/admin` (SANGAT disarankan, hemat waktu —
   device ZKTeco & log mentah sudah rapi di sana), atau dihapus total?
2. **URL utama**: UI AeroDeck mengambil alih `/` (login → dashboard), Filament tetap di `/admin`?
3. **"Reset"**: maksud Anda = **tata ulang UI/UX** (rekomendasi, data & backend aman) **bukan** hapus
   database/kode dari nol — mohon konfirmasi.
4. **Prioritas**: mulai dari **F0+F1** (fondasi + Dashboard) dulu, atau Anda ingin urutan lain?

## 9. Risiko & catatan

- Server produksi **tidak punya Node** → semua build aset dilakukan di server AI lalu `public/build` di-deploy via scp (pola yang sudah berjalan).
- Font & Chart.js **self-host** (jangan CDN) — halaman harus tetap cepat & mandiri.
- Integrasi ZKTeco ADMS & scheduler recompute **tidak disentuh** — absensi tetap berjalan selama perombakan UI.
- `APP_DEBUG=true` di produksi masih terbuka (temuan lama, belum diperbaiki) — di luar lingkup rencana ini, tapi sebaiknya ditangani.
