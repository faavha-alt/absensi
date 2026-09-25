@extends('layouts.app')

@section('title', $employee->name.' · Data Pegawai')
@section('heading', $employee->name)
@section('subheading', trim(($employee->position ?: 'Pegawai').' · '.($employee->department ?: 'Tanpa departemen'), ' ·'))

@php
    $badge = fn (array $s) => $s['class'] ?? 'badge-alpa';
    $todayRecap = $employee->attendanceRecaps()->whereDate('date', now()->toDateString())->first();
    $todayStatus = $employee->is_admin
        ? ['key' => 'admin', 'label' => 'Admin device', 'class' => 'badge-alpa']
        : ($todayRecap ? $todayRecap->status() : ['key' => 'belum', 'label' => 'Belum Absen', 'class' => 'badge-alpa']);
    $prevMonth = $month->copy()->subMonth();
    $nextMonth = $month->copy()->addMonth();
@endphp

@section('actions')
    <a href="{{ route('pegawai.edit', $employee) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">Edit</a>
    <form method="POST" action="{{ route('pegawai.destroy', $employee) }}" onsubmit="return confirm('Hapus pegawai {{ $employee->name }}? Riwayat scan mentah tetap tersimpan.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-2.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-100">Hapus</button>
    </form>
@endsection

@section('content')
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    {{-- Profil --}}
    <div class="apple-glass-card flex flex-wrap items-center justify-between gap-4 rounded-3xl p-6">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-gradient-to-tr from-slate-900 via-slate-800 to-indigo-950 text-lg font-bold text-white shadow-lg shadow-slate-900/15">
                {{ strtoupper(substr($employee->name, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-base font-extrabold tracking-tight text-slate-900">{{ $employee->name }}</h2>
                <p class="font-mono-num text-[11px] font-medium text-slate-400">NIP {{ $employee->employee_code }} · PIN {{ $employee->zkteco_pin ?: '—' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge-status {{ $badge($todayStatus) }}"><span class="dot"></span>{{ $todayStatus['label'] }} hari ini</span>
        </div>
    </div>

    {{-- KPI pribadi --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kehadiran {{ $year }}</p>
            <p class="font-mono-num mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $summary['hadir'] }}</p>
            <p class="font-mono-num mt-1 text-[11px] font-medium text-slate-400">/ {{ $summary['hari_kerja'] }} hari kerja · {{ $summary['persen_hadir'] }}%</p>
        </div>
        <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Keterlambatan</p>
            <p class="font-mono-num mt-2 text-3xl font-extrabold tracking-tight text-rose-600">{{ $summary['telat'] }}</p>
            <p class="font-mono-num mt-1 text-[11px] font-medium text-slate-400">{{ $summary['menit_telat'] }} menit total</p>
        </div>
        <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pulang Cepat</p>
            <p class="font-mono-num mt-2 text-3xl font-extrabold tracking-tight text-amber-600">{{ $summary['pulang'] }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">kali dalam setahun</p>
        </div>
        <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Ketepatan Waktu</p>
            <p class="font-mono-num mt-2 text-3xl font-extrabold tracking-tight text-indigo-600">{{ $summary['persen_tepat'] }}%</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">hadir tanpa telat</p>
        </div>
    </div>

    {{-- Kalender + grafik --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div class="apple-glass-card rounded-3xl p-6 lg:col-span-7">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Kalender Kehadiran</h2>
                <div class="flex items-center gap-1">
                    <a href="{{ route('pegawai.show', ['employee' => $employee, 'bulan' => $prevMonth->format('Y-m'), 'tahun' => $prevMonth->year]) }}"
                       class="rounded-xl border border-slate-200 bg-white px-2.5 py-1 font-mono-num text-xs text-slate-500 transition hover:bg-slate-50">‹</a>
                    <span class="px-2 font-mono-num text-xs font-bold text-slate-700">{{ $month->translatedFormat('M Y') }}</span>
                    <a href="{{ route('pegawai.show', ['employee' => $employee, 'bulan' => $nextMonth->format('Y-m'), 'tahun' => $nextMonth->year]) }}"
                       class="rounded-xl border border-slate-200 bg-white px-2.5 py-1 font-mono-num text-xs text-slate-500 transition hover:bg-slate-50">›</a>
                </div>
            </div>

            <div class="mb-3 flex flex-wrap gap-x-3 gap-y-1.5 text-[10px] font-semibold text-slate-500">
                <span class="badge-status badge-hadir"><span class="dot"></span>Hadir</span>
                <span class="badge-status badge-telat"><span class="dot"></span>Telat</span>
                <span class="badge-status badge-pulang"><span class="dot"></span>Pulang Cepat</span>
                <span class="badge-status badge-alpa"><span class="dot"></span>Alpa</span>
                <span class="badge-status badge-libur"><span class="dot"></span>Libur</span>
            </div>

            <div class="grid grid-cols-7 gap-1.5">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $w)
                    <div class="pb-1 text-center text-[10px] font-bold uppercase text-slate-400">{{ $w }}</div>
                @endforeach

                @for ($i = 1; $i < $calendar[0]['date']->dayOfWeekIso; $i++)
                    <div></div>
                @endfor

                @foreach ($calendar as $cell)
                    <div class="cal-cell {{ $cell['status']['c'] }} {{ $cell['is_today'] ? 'cal-today' : '' }}"
                         @if ($cell['status']['title']) title="{{ $cell['date']->format('d M Y') }} · {{ $cell['status']['title'] }}" @endif>
                        <span class="cal-num">{{ $cell['day'] }}</span>
                        <span class="cal-dot"></span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="apple-glass-card rounded-3xl p-6 lg:col-span-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Tren Bulanan {{ $year }}</h2>
                <form method="GET" action="{{ route('pegawai.show', $employee) }}">
                    <input type="hidden" name="bulan" value="{{ $month->format('Y-m') }}">
                    <select name="tahun" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-2 py-1 font-mono-num text-xs text-slate-600">
                        @foreach (range(now()->year, max(2024, now()->year - 4)) as $y)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="empTrend"></canvas>
            </div>
        </div>
    </div>

    {{-- Riwayat scan --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-900">Riwayat Scan Terakhir</h2>
            <span class="rounded-full border border-slate-200/60 bg-slate-50 px-2.5 py-0.5 font-mono-num text-[10px] font-bold text-slate-500">{{ $recentScan->count() }} data</span>
        </div>
        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3">#</th>
                        <th class="px-6 py-3">Waktu Scan</th>
                        <th class="px-6 py-3">PIN</th>
                        <th class="px-6 py-3 text-center">Verifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($recentScan as $i => $scan)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-3 text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-6 py-3 font-mono-num">{{ $scan->punched_at?->translatedFormat('d M Y · H:i:s') }}</td>
                            <td class="px-6 py-3 font-mono-num">{{ $scan->pin }}</td>
                            <td class="px-6 py-3 text-center font-mono-num text-slate-500">{{ $scan->verify_type ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-xs font-medium text-slate-400">Belum ada riwayat scan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            const el = document.getElementById('empTrend');
            if (!el || !window.Chart) return;

            new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
                    datasets: [
                        { label: 'Hadir', data: @json(array_values(array_map(fn ($m) => $m['hadir'], $monthly))), backgroundColor: '#10b981', borderRadius: 6, stack: 's' },
                        { label: 'Telat', data: @json(array_values(array_map(fn ($m) => $m['telat'], $monthly))), backgroundColor: '#e11d48', borderRadius: 6, stack: 's' },
                        { label: 'Pulang Cepat', data: @json(array_values(array_map(fn ($m) => $m['pulang'], $monthly))), backgroundColor: '#f59e0b', borderRadius: 6, stack: 's' },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, font: { size: 10 } } } },
                    scales: {
                        x: { stacked: true, ticks: { font: { size: 10 } }, grid: { display: false } },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: 'rgba(226,232,240,0.5)' } },
                    },
                },
            });
        });
    </script>
@endpush
