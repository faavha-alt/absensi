<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceRecapCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    /* =====================================================================
     | Jam Kerja
     ===================================================================== */

    public function jamKerja(): View
    {
        return view('pengaturan.jam-kerja', ['schedule' => WorkSchedule::current()]);
    }

    public function saveJamKerja(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'early_leave_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
        ]);

        WorkSchedule::current()->update($data);

        $count = app(AttendanceRecapCalculator::class)->recomputeAll();

        return back()->with('status', "Pengaturan jam kerja disimpan. Rekap dihitung ulang ({$count} hari-pegawai).");
    }

    /* =====================================================================
     | Hari Libur
     ===================================================================== */

    public function libur(Request $request): View
    {
        $tahun = (int) $request->query('tahun', now()->year);

        return view('pengaturan.libur', [
            'tahun' => $tahun,
            'holidays' => Holiday::whereYear('date', $tahun)->orderBy('date')->get(),
            'availableYears' => range(now()->year + 1, max(2024, now()->year - 3)),
        ]);
    }

    public function storeLibur(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        Holiday::updateOrCreate(['date' => $data['date']], ['name' => $data['name']]);

        $count = app(AttendanceRecapCalculator::class)->recomputeAll();

        return back()->with('status', "Hari libur disimpan. Rekap dihitung ulang ({$count} hari-pegawai).");
    }

    public function destroyLibur(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        $count = app(AttendanceRecapCalculator::class)->recomputeAll();

        return back()->with('status', "Hari libur dihapus. Rekap dihitung ulang ({$count} hari-pegawai).");
    }

    /* =====================================================================
     | Akun Admin
     ===================================================================== */

    public function akun(): View
    {
        return view('pengaturan.akun', ['users' => User::orderBy('name')->get()]);
    }

    public function storeAkun(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return back()->with('status', "Akun {$data['name']} berhasil ditambahkan.");
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => $data['password']]);

        return back()->with('status', "Kata sandi {$user->name} diperbarui.");
    }

    public function destroyAkun(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['akun' => 'Tidak bisa menghapus akun yang sedang dipakai.']);
        }

        if (User::count() <= 1) {
            return back()->withErrors(['akun' => 'Minimal harus ada satu akun admin.']);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('status', "Akun {$name} dihapus.");
    }
}
