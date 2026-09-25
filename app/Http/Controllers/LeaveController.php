<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Services\AttendanceRecapCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $tahun = (int) $request->query('tahun', now()->year);
        $jenis = $request->query('jenis');
        $pegawaiId = $request->query('pegawai');

        $leaves = Leave::query()
            ->with(['employee', 'creator'])
            ->whereYear('start_date', $tahun)
            ->when($jenis, fn ($q) => $q->where('type', $jenis))
            ->when($pegawaiId, fn ($q) => $q->where('employee_id', $pegawaiId))
            ->orderByDesc('start_date')
            ->paginate(25)
            ->withQueryString();

        return view('izin.index', [
            'leaves' => $leaves,
            'tahun' => $tahun,
            'jenis' => $jenis,
            'pegawaiId' => $pegawaiId,
            'employees' => Employee::where('is_admin', false)->orderBy('name')->get(),
            'types' => Leave::TYPES,
            'availableYears' => range(now()->year + 1, max(2024, now()->year - 3)),
            'editing' => $request->filled('edit') ? Leave::find($request->integer('edit')) : null,
            'summary' => $this->summary($tahun),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $leave = Leave::create($data);
        $count = $this->recompute($leave);

        return back()->with('status', "Izin/sakit/cuti dicatat. {$count} hari dihitung ulang.");
    }

    public function update(Request $request, Leave $leave): RedirectResponse
    {
        $old = $leave->replicate();

        $leave->update($this->validated($request));
        $leave->refresh();

        // hitung ulang rentang lama & baru (kalau tanggalnya berubah)
        $count = $this->recomputeRange($old);
        $count += $this->recompute($leave);

        return redirect()
            ->route('izin.index')
            ->with('status', "Perubahan disimpan. {$count} hari dihitung ulang.");
    }

    public function destroy(Leave $leave): RedirectResponse
    {
        $snapshot = $leave->replicate();
        $leave->delete();

        $count = $this->recomputeRange($snapshot);

        return back()->with('status', "Catatan dihapus. {$count} hari dihitung ulang.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(Leave::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:180'],
        ]);
    }

    protected function recompute(Leave $leave): int
    {
        return $this->recomputeRange($leave);
    }

    protected function recomputeRange(Leave $leave): int
    {
        $employee = Employee::find($leave->employee_id);

        if (! $employee) {
            return 0;
        }

        $calculator = app(AttendanceRecapCalculator::class);
        $count = 0;

        for ($date = $leave->start_date->copy(); $date->lte($leave->end_date); $date->addDay()) {
            $calculator->recomputeForEmployeeAndDate($employee, $date->copy());
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string, int>
     */
    protected function summary(int $tahun): array
    {
        $base = Leave::whereYear('start_date', $tahun);

        return [
            'total' => (clone $base)->count(),
            'izin' => (clone $base)->where('type', 'izin')->count(),
            'sakit' => (clone $base)->where('type', 'sakit')->count(),
            'cuti' => (clone $base)->where('type', 'cuti')->count(),
            'dinas' => (clone $base)->where('type', 'dinas_luar')->count(),
        ];
    }
}
