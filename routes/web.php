<?php

use App\Http\Controllers\AnalitikController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\RekapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Aplikasi (butuh login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /**
     * Halaman modul yang belum dibangun (placeholder sesuai roadmap PLAN.md).
     */
    $soon = fn (string $title, string $phase, string $description) => view('soon', [
        'title' => $title,
        'phase' => $phase,
        'description' => $description,
    ]);

    Route::get('/kehadiran', fn () => $soon('Kehadiran Hari Ini', 'Fase 1 · Pantau', 'Papan real-time: sudah masuk, telat, belum absen, dan sudah pulang.'))->name('kehadiran.index');
    Route::get('/perangkat', fn () => $soon('Perangkat', 'Fase 5 · Pantau', 'Status perangkat ZKTeco, waktu sinkronisasi terakhir, dan log device.'))->name('perangkat.index');

    Route::get('/pegawai', [EmployeeController::class, 'index'])->name('pegawai.index');
    Route::get('/pegawai/create', [EmployeeController::class, 'create'])->name('pegawai.create');
    Route::post('/pegawai', [EmployeeController::class, 'store'])->name('pegawai.store');
    Route::get('/pegawai/{employee}', [EmployeeController::class, 'show'])->name('pegawai.show');
    Route::get('/pegawai/{employee}/edit', [EmployeeController::class, 'edit'])->name('pegawai.edit');
    Route::put('/pegawai/{employee}', [EmployeeController::class, 'update'])->name('pegawai.update');
    Route::delete('/pegawai/{employee}', [EmployeeController::class, 'destroy'])->name('pegawai.destroy');

    Route::get('/rekap-harian', [RekapController::class, 'harian'])->name('rekap.harian');
    Route::get('/rekap-harian/export', [RekapController::class, 'exportHarian'])->name('rekap.harian.export');
    Route::get('/rekap-bulanan', [RekapController::class, 'bulanan'])->name('rekap.bulanan');
    Route::get('/rekap-bulanan/export', [RekapController::class, 'exportBulanan'])->name('rekap.bulanan.export');
    Route::get('/rekap-tahunan', [AnalitikController::class, 'tahunan'])->name('rekap.tahunan');
    Route::get('/rekap-tahunan/export', [AnalitikController::class, 'exportTahunan'])->name('rekap.tahunan.export');

    Route::get('/performa', [AnalitikController::class, 'performa'])->name('performa.index');
    Route::get('/performa/export', [AnalitikController::class, 'exportPerforma'])->name('performa.export');

    Route::get('/pengaturan/jam-kerja', fn () => $soon('Jam Kerja & Toleransi', 'Fase 5 · Pengaturan', 'Atur jam masuk/pulang dan toleransi — rekap dihitung ulang otomatis.'))->name('pengaturan.jam-kerja');
    Route::get('/pengaturan/libur', fn () => $soon('Hari Libur', 'Fase 5 · Pengaturan', 'Kelola tanggal libur nasional dan cuti bersama.'))->name('pengaturan.libur');
    Route::get('/pengaturan/akun', fn () => $soon('Akun & Keamanan', 'Fase 5 · Pengaturan', 'Kelola akun admin dan keamanan endpoint alat absensi.'))->name('pengaturan.akun');
});
