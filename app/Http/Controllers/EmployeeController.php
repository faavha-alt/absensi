<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use App\Services\AttendancePerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Syofyanzuhad\FilamentZktecoAdms\Models\AttendanceLog;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $dept = $request->query('departemen');
        $status = $request->query('status');

        $employees = Employee::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('employee_code', 'like', "%{$q}%")
                ->orWhere('zkteco_pin', 'like', "%{$q}%")))
            ->when($dept, fn ($query) => $query->where('department', $dept))
            ->when($status === 'admin', fn ($query) => $query->where('is_admin', true))
            ->when($status === 'pegawai', fn ($query) => $query->where('is_admin', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments = Employee::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $recapsToday = AttendanceRecap::whereDate('date', Carbon::today())->get()->keyBy('employee_id');

        return view('pegawai.index', compact('employees', 'departments', 'q', 'dept', 'status', 'recapsToday'));
    }

    public function show(Request $request, Employee $employee): View
    {
        $service = app(AttendancePerformanceService::class);

        $year = (int) $request->query('tahun', now()->year);
        $monthParam = (string) $request->query('bulan', '');
        $month = $monthParam !== ''
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::today()->startOfMonth();

        $monthly = $service->monthlyForEmployee($employee->id, $year);

        $scans = AttendanceLog::query()
            ->where('pin', $employee->zkteco_pin)
            ->orderByDesc('punched_at')
            ->limit(20)
            ->get();

        return view('pegawai.show', [
            'employee' => $employee,
            'year' => $year,
            'month' => $month,
            'summary' => $this->summarize($monthly),
            'monthly' => $monthly,
            'calendar' => $service->calendarForEmployee($employee->id, $month),
            'recentScan' => $scans,
        ]);
    }

    public function create(): View
    {
        return view('pegawai.form', ['employee' => new Employee()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $employee = Employee::create($data);

        return redirect()
            ->route('pegawai.show', $employee)
            ->with('status', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Employee $employee): View
    {
        return view('pegawai.form', ['employee' => $employee]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validated($request, $employee->id));

        return redirect()
            ->route('pegawai.show', $employee)
            ->with('status', 'Data pegawai diperbarui. Jalankan "Hitung ulang rekap" bila mapping PIN berubah.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $name = $employee->name;
        $employee->delete();

        return redirect()
            ->route('pegawai.index')
            ->with('status', "Pegawai {$name} dihapus (riwayat scan mentah tetap tersimpan).");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($ignoreId)],
            'position' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'zkteco_pin' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'zkteco_pin')->ignore($ignoreId)],
            'is_admin' => ['nullable', 'boolean'],
        ]) + ['is_admin' => $request->boolean('is_admin')];
    }

    /**
     * @param  array<int, array{hadir:int, telat:int, pulang:int, menit_telat:int, hari_kerja:int}>  $monthly
     * @return array<string, float|int>
     */
    protected function summarize(array $monthly): array
    {
        $hadir = array_sum(array_column($monthly, 'hadir'));
        $telat = array_sum(array_column($monthly, 'telat'));
        $pulang = array_sum(array_column($monthly, 'pulang'));
        $menit = array_sum(array_column($monthly, 'menit_telat'));
        $hariKerja = array_sum(array_column($monthly, 'hari_kerja'));

        return [
            'hadir' => $hadir,
            'telat' => $telat,
            'pulang' => $pulang,
            'menit_telat' => $menit,
            'hari_kerja' => $hariKerja,
            'persen_hadir' => $hariKerja > 0 ? round($hadir / $hariKerja * 100, 1) : 0,
            'persen_tepat' => $hadir > 0 ? round(($hadir - $telat) / $hadir * 100, 1) : 0,
        ];
    }
}
