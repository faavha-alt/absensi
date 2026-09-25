# SPESIFIKASI SISTEM ABSENSI PEGAWAI (AeroDeck)

> Dokumen ini melengkapi `PLAN.md` (roadmap) dengan **spesifikasi matang**: tata letak menu, peran akses,
> spesifikasi tiap layar, design system, pola UX, definisi metrik, dan non-fungsional.
> Acuan visual: skill `ui-dashboard-aerodeck`. Ruang lingkup: **absensi/kehadiran saja** (tanpa gaji/kontrak).

---

## 0. Prinsip Desain

1. **Satu pertanyaan, satu layar.** Tiap halaman menjawab satu kebutuhan utama pengguna.
2. **Informasi dulu, hiasan kemudian.** Angka penting terlihat dalam < 3 detik tanpa scroll.
3. **Konsistensi mutlak.** Semua metrik memakai definisi & badge yang sama di seluruh aplikasi (satu sumber: `AttendanceRecap::status()`).
4. **Kepadatan terkendali.** Maksimal 4 KPI per baris, 7 item per grup menu, tabel ≤ 8 kolom.
5. **Selalu ada jalan keluar.** Setiap state kosong/error punya aksi lanjutan yang jelas.
6. **Aksesibel & cepat.** Kontras AA, navigasi keyboard, target muat < 1,5 s.

---

## 1. Peran & Hak Akses (RBAC)

| Kemampuan | Super Admin | Admin Kepegawaian | Pimpinan (lihat saja) |
| :- | :-: | :-: | :-: |
| Dashboard & Kehadiran Hari Ini | ✓ | ✓ | ✓ |
| Data Pegawai (lihat) | ✓ | ✓ | ✓ |
| Data Pegawai (ubah / mapping PIN) | ✓ | ✓ | — |
| Rekap Harian/Bulanan/Tahunan (lihat) | ✓ | ✓ | ✓ |
| Export | ✓ | ✓ | ✓ |
| Performa & Analitik | ✓ | ✓ | ✓ |
| Jam Kerja & Hari Libur | ✓ | ✓ | — |
| Perangkat (device, sync, log mentah) | ✓ | — | — |
| Akun & Keamanan | ✓ | — | — |

Peran disimpan sebagai kolom `role` di `users` (enum: `super_admin`, `hr`, `viewer`). Menu & aksi **disembunyikan** (bukan hanya diblokir) sesuai peran.

---

## 2. Arsitektur Informasi & Tata Letak Menu

### 2.1 Struktur Sidebar (final)

```
┌ Absensi · Pro            [brand]
│ Kepegawaian · MIPA UNS
├ ◉ Perangkat Alat: Online — [status pill]
├
├ PANTAU
│  ▸ Dashboard                    /                    [grid]
│  ▸ Kehadiran Hari Ini           /kehadiran          [clock]  badge: belum absen
│  ▸ Perangkat                    /perangkat          [device] badge: offline
├ KEPEGAWAIAN
│  ▸ Data Pegawai                 /pegawai            [users]
├ REKAP
│  ▸ Rekap Harian                 /rekap-harian       [calendar-day]
│  ▸ Rekap Bulanan                /rekap-bulanan      [calendar]
│  ▸ Rekap Tahunan                /rekap-tahunan      [chart]
├ ANALITIK
│  ▸ Performa Pegawai             /performa           [trending]
├ PENGATURAN
│  ▸ Jam Kerja                    /pengaturan/jam-kerja [cog]
│  ▸ Hari Libur                   /pengaturan/libur   [flag]
│  ▸ Akun & Keamanan              /pengaturan/akun    [shield]
└ [avatar] Nama — email                          [logout]
```

**Aturan menu**
- Urutan = **frekuensi pakai** (Pantau harian di atas; pengaturan di bawah).
- **Badge angka** hanya untuk hal yang butuh tindakan: *belum absen* (netral), *offline* (merah).
- Item belum dibangun diberi label kecil **`soon`** (abu) dan tetap bisa diklik ke halaman placeholder.
- Item yang tidak boleh diakses peran tertentu **tidak dirender**.
- Maks 7 item/grup; grup kosong tidak dirender.

### 2.2 Header (top bar)

```
[ Judul Halaman + subjudul ]        [ tanggal hari ini ] [🔔 notif ] [ aksi cepat ]
```
- **Aksi cepat** (kanan): tombol kontekstual per halaman (mis. "Export" di Rekap, "Tambah" di Pegawai).
- **Notifikasi**: lonceng + badge; isi: device offline, pegawai belum absen melewati jam kerja, konflik data.
- Di halaman daftar: **pencarian global** (`/` atau `⌘K`) — cari pegawai/PIN.

### 2.3 Perilaku responsif

| Lebar | Sidebar | Konten |
| :- | :- | :- |
| ≥ 1024px | tetap (floating, w-64) | grid penuh (12 kolom) |
| 640–1023px | drawer (tombol hamburger) | grid 2 kolom |
| < 640px | drawer + bottom-nav (4 aksi utama) | 1 kolom, tabel → kartu |

### 2.4 Peta URL & nama route

| Route | Nama | Halaman |
| :- | :- | :- |
| `/` | `dashboard` | Dashboard |
| `/kehadiran` | `kehadiran.index` | Kehadiran Hari Ini |
| `/perangkat` | `perangkat.index` | Perangkat |
| `/pegawai`, `/pegawai/{id}` | `pegawai.index`, `pegawai.show` | Data Pegawai + detail |
| `/rekap-harian` | `rekap.harian` | Rekap Harian |
| `/rekap-bulanan` | `rekap.bulanan` | Rekap Bulanan |
| `/rekap-tahunan` | `rekap.tahunan` | Rekap Tahunan |
| `/performa` | `performa.index` | Performa Pegawai |
| `/pengaturan/{jam-kerja,libur,akun}` | `pengaturan.*` | Pengaturan |

---

## 3. Spesifikasi Tiap Layar

Format: **Tujuan · Tata letak · Komponen · Aksi · State**.

### 3.1 Dashboard
- **Tujuan**: kondisi hari ini & tren sekilas.
- **Layout**: baris KPI (4) → grid 12: grafik tren (8) + scan terbaru (4) → tabel kehadiran hari ini (12).
- **Komponen**: KPI card (label, nilai mono, delta/pill), line chart (Hadir/Telat), feed list, tabel status.
- **Aksi**: ganti rentang 7/30/90 hari (tersimpan di URL `?range=`), klik baris pegawai → detail, klik "Kehadiran Hari Ini".
- **State**: kosong (belum ada scan hari ini → ilustrasi + teks), device offline (banner kuning di atas).

### 3.2 Kehadiran Hari Ini
- **Tujuan**: papan real-time, siapa sudah/belum.
- **Layout**: bar filter (tanggal, departemen, status) → chip ringkas (Hadir n · Telat n · Belum n · Pulang n) → tabel.
- **Kolom**: Pegawai · Jabatan/Dept · Masuk · Pulang · Durasi · Status · (aksi: lihat).
- **Aksi**: ganti tanggal, filter status, cari nama, export, auto-refresh 60 s (toggle).
- **State**: "semua sudah absen" (hijau), belum ada scan, hari libur (banner "Hari Libur: <nama>").

### 3.3 Perangkat
- **Tujuan**: status alat & kesehatan sinkronisasi.
- **Layout**: KPI (device online, scan hari ini, last sync) → kartu per device (SN, model, IP, firmware, terakhir aktif, indikator online) → tabel log sinkronisasi.
- **Aksi**: lihat log mentah, kirim perintah device (tarik USERINFO/ATTLOG), atur whitelist SN.
- **State**: tidak ada device, device offline lama (peringatan merah).

### 3.4 Data Pegawai (daftar)
- **Layout**: bar (cari + filter departemen/status/admin) → tabel: Nama · NIP · Jabatan · Departemen · PIN · Status admin · (aksi edit).
- **Aksi**: tambah, edit, hapus (konfirmasi), export, klik → detail.
- **State**: kosong → CTA "Tambah pegawai".

### 3.5 Detail Pegawai
- **Layout**: header profil (nama, NIP, jabatan, badge status hari ini) → KPI pribadi 4 (kehadiran, telat, pulang cepat, % tepat) → grid 12: kalender bulanan (8) + ringkasan performa mini (4) → tabel riwayat scan mentah → tab: Kalender · Rekap · Scan.
- **Aksi**: pilih bulan (navigasi ‹ ›), edit data/PIN, export rekap pribadi.
- **State**: pegawai admin device (catatan "tidak dihitung"), belum ada data.

### 3.6 Rekap Harian
- **Layout**: filter (rentang tanggal, pegawai, status) → ringkasan → tabel: Tanggal · Pegawai · Masuk · Pulang · Telat(menit) · Pulang Cepat(menit) · Status.
- **Aksi**: filter, export Excel (queued → notifikasi selesai), simpan filter di URL.

### 3.7 Rekap Bulanan
- **Layout**: filter (bulan, tahun) → KPI ringkas → **matriks pegawai × tanggal** (sel H/T/P/A/L berwarna, legenda) → tabel ringkasan per pegawai.
- **Aksi**: klik sel → drawer detail hari itu; export Excel; navigasi bulan.
- **State**: bulan berjalan (tanggal mendatang = sel kosong).

### 3.8 Rekap Tahunan
- **Layout**: filter tahun → KPI tahunan → grafik batang 12 bulan (Hadir/Telat/Pulang) + garis menit telat → **peringkat pegawai** (ketepatan %, badge).
- **Aksi**: ganti tahun, export, klik pegawai → detail.

### 3.9 Performa Pegawai
- **Layout**: filter (pegawai, tahun) → KPI (kehadiran, %tepat, telat, menit) → grafik batang bulanan (Hadir/Telat/Pulang) + garis menit telat → tabel bulanan.
- **Aksi**: ganti pegawai/tahun, export; pintasan ke detail pegawai.

### 3.10 Pengaturan — Jam Kerja
- **Layout**: kartu form (jam masuk, toleransi telat, jam pulang, toleransi pulang cepat) + catatan efek.
- **Aksi**: **Simpan** → validasi → simpan → **jalankan recompute rekap** → toast "Tersimpan & rekap dihitung ulang (n hari-pegawai)".
- **State**: konfirmasi bila mengubah jam kerja (berdampak historis).

### 3.11 Pengaturan — Hari Libur
- **Layout**: daftar tanggal libur (tahun berjalan) + form tambah (tanggal, nama) + import massal.
- **Aksi**: tambah/hapus; perubahan → recompute.

### 3.12 Pengaturan — Akun & Keamanan
- **Layout**: tabel akun admin (nama, email, peran) + form tambah/edit + kartu keamanan endpoint (auto-register, whitelist SN/IP, log audit).
- **Aksi**: kelola akun (cegah hapus akun sendiri), aktifkan whitelist.

---

## 4. Design System

### 4.1 Token
| Token | Nilai |
| :- | :- |
| Background | `#F4F4F6` |
| Surface (glass) | `rgba(255,255,255,0.88)` + blur 24px |
| Border | `rgba(255,255,255,0.7)` / `#EEF0F4` |
| Aksen | indigo `#4F46E5` |
| Status | emerald `#10B981` (ok) · amber `#F59E0B` (warning) · rose `#E11D48` (error) · slate (netral) |
| Radius | kartu `1.5rem` (3xl) · kontrol `1rem` (2xl) · badge `full` |
| Shadow | `inset 0 1px 0 #fff` + 2 lapis lembut |
| Font UI | Plus Jakarta Sans (400–800) |
| Font angka | JetBrains Mono (`tnum`) — semua angka & waktu |

### 4.2 Skala tipografi
- Judul halaman 14px/800 · Subjudul 11px/500 slate-400
- Nilai KPI 30px/800 mono · Label KPI 11px/700 uppercase slate-400
- Isi tabel 12px · Header tabel 10px/700 uppercase slate-400
- Body 12–13px · Meta 10–11px

### 4.3 Spacing & grid
Ritme 4px. Padding kartu 20–24px. Jarak antar-blok 20px (`space-y-5`). Grid 12 kolom; gutter 20px. KPI: 1/2/4 kolom (sm/lg).

### 4.4 Inventaris komponen
KPI card · Glass card · Chart card · Table card · Badge status · Chip filter · Button (primary `bg-slate-900`, sekunder putih-border, ghost, danger) · Input/Select/DateRange · Tabs · Drawer/Modal · Toast · Tooltip · Pagination · Empty state · Skeleton loading · Avatar · Progress bar · Calendar cell.

### 4.5 State & interaksi
- **Hover**: `translateY(-2px)` (kartu interaktif), hover baris `bg-slate-50`.
- **Focus**: ring indigo 4px/5% (terlihat).
- **Active**: `scale-95` pada tombol.
- **Disabled**: opacity 50 + cursor-not-allowed.
- **Loading**: skeleton (bukan spinner penuh halaman).

### 4.6 Aksesibilitas
Kontras AA; semua aksi punya label/aria; urutan tab logis; status tidak hanya warna (selalu ada teks/ikon); target sentuh ≥ 36px.

---

## 5. Pola UX

1. **Filter tersimpan di URL** (`?bulan=&tahun=&status=`) → bisa di-bookmark & di-share.
2. **Pencarian global** (`/` fokus) untuk pegawai & PIN.
3. **Export**: tombol di header halaman; proses di antrean → notifikasi hasil.
4. **Aksi destruktif**: modal konfirmasi + sebutkan dampak (mis. "hapus pegawai X, riwayat absen tetap tersimpan?").
5. **Feedback**: toast kanan-atas (sukses/error), auto-hilang 4 s.
6. **Auto-refresh** opsional (30/60 s) di layar real-time (Kehadiran Hari Ini, Perangkat) dengan indikator jam update.
7. **Deep-link**: klik pegawai/sel tanggal membuka drawer dengan URL (`?pegawai=`, `?tanggal=`).
8. **Keyboard**: `/` cari · `g d` dashboard · `Esc` tutup drawer/modal.

---

## 6. Definisi Metrik (WAJIB konsisten satu sumber)

| Metrik | Rumus |
| :- | :- |
| **Hadir** | jumlah hari kerja dengan ≥ 1 scan |
| **Telat** | hari dengan `late_minutes > 0`; menit = `check_in − (jam_masuk + toleransi)` |
| **Pulang cepat** | hari dengan `early_leave_minutes > 0`; menit = `(jam_pulang − toleransi) − check_out` |
| **Alpa** | hari kerja tanpa scan (bukan libur/akhir pekan) |
| **Libur** | tanggal ada di tabel `holidays`, atau Sabtu/Minggu |
| **% Kehadiran** | `hadir ÷ hari kerja` (tahun berjalan: hari kerja **sampai hari ini**) |
| **% Ketepatan** | `(hadir − telat) ÷ hadir` |
| **Check-out valid** | scan terakhir ≥ 12:00 **atau** ≥ 4 jam setelah check-in; jika tidak → `null` (dianggap tidak scan pulang) |
| **Eksepsi** | pegawai `is_admin=true` tidak dihitung |

---

## 7. Non-Fungsional

- **Performa**: index DB pada `attendance_recaps(date)`, `(employee_id,date)`, `zkteco_attendance_logs(pin,punched_at)`. Dashboard ≤ 4 query berat; cache ringkas 60 s.
- **Keamanan**: RBAC; CSRF; validasi input; endpoint alat tetap `auto_register=false` + opsi whitelist; audit log ADMS dipertahankan; **`APP_DEBUG=true` produksi harus segera dimatikan** (temuan lama).
- **Integritas**: recompute otomatis saat jam kerja/libur berubah; scheduler harian 04:00; tombol "Hitung ulang" manual.
- **Backup**: `clpctl db:export` harian (sudah ada), retensi 14 hari; offsite menyusul.
- **Observabilitas**: log ADMS, log error, halaman status device.
- **Dependensi**: Tailwind+Vite (build lokal), Chart.js self-host, font self-host — **tanpa CDN**.

---

## 8. Roadmap & Kriteria Terima

| Fase | Isi | Kriteria terima |
| :- | :- | :- |
| **F0** ✅ | Fondasi AeroDeck, login, layout | login 200, `/admin` hilang, alat tetap jalan |
| **F1** ✅ | Dashboard | KPI + tren + scan + tabel hari ini ter-render |
| **F2** | Pegawai (daftar + detail + kalender) | CRUD, kalender akurat, % konsisten |
| **F3** | Rekap Harian + Bulanan + export | filter tersimpan di URL, export berhasil |
| **F4** | Performa + Tahunan (Analitik) | grafik benar, ranking akurat |
| **F5** | Pengaturan (Jam Kerja, Libur, Akun, Perangkat) | simpan → recompute jalan |
| **F6** | Poles: responsif, aksesibilitas, keyboard, skeleton | audit kontras & mobile lolos |

Setiap fase: `php artisan test` hijau, verifikasi server (bukan asumsi), commit + catat `PROGRESS.md`.

---

## 9. Risiko

- Server produksi **tanpa Node** → build di server AI, deploy `public/build` via scp.
- Repo lokal & server belum sinkron (server bukan git repo).
- Menghapus Filament = perlu bangun ulang UI kelola device/log (F5) — sementara lewat halaman Perangkat.
- Scheduler recompute bergantung cron `schedule:run` (sudah dipasang).
