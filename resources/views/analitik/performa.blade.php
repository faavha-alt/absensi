@extends('layouts.app')

@section('title', 'Performa Pegawai · Absensi')
@section('heading', 'Performa Pegawai')
@section('subheading', ($employee?->name ?? 'Pilih pegawai').' · '.$year)

@php
    $monthlyValues = array_values($monthly);
    $hadirData = array_map(fn ($m) => $m['hadir'], $monthlyValues);
    $telatData = array_map(fn ($m) => $m['telat'], $monthlyValues);
    $pulangData = array_map(fn ($m) => $m['pulang'], $monthlyValues);
    $menitData = array_map(fn ($m) => $m['menit_telat'], $monthlyValues);
@endphp

@section('actions')
    <a href="{{ route('performa.export', ['pegawai' => $employeeId, 'tahun' => $year]) }}"
       class="rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
        Export Excel
    </a>
@endsection

@section('content')
    {{-- Filter --}}
    <form method="GET" action="{{ route('performa.index') }}" class="apple-glass-card flex flex-wrap items-end gap-3 rounded-3xl p-4">
        <div class="min-w-[16rem] flex-1">
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Pegawai</label>
            <select name="pegawai" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" @selected($employeeId === $emp->id)>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Tahun</label>
            <select name="tahun" onchange="this.form.submit()" class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                @foreach ($availableYears as $y)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        @if ($employee)
            <a href="{{ route('pegawai.show', $employee) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Lihat Profil →</a>
        @endif
    </form>

    {{-- KPI --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tingkat Kehadiran</p>
            <p class="font-mono-num mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $summary['persen_hadir'] }}%</p>
            <p class="font-mono-num mt-1 text-[11px] font-medium text-slate-400">{{ $summary['hadir'] }} / {{ $summary['hari_kerja'] }} hari kerja</p>
        </div>
        <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Ketepatan Waktu</p>
            <p class="font-mono-num mt-2 text-3xl font-extrabold tracking-tight text-indigo-600">{{ $summary['persen_tepat'] }}%</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">hadir tanpa telat</p>
        </div>
        <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Keterlambatan</p>
            <p class="font-mono-num mt-2 text-3xl font-extrabold tracking-tight text-rose-600">{{ $summary['telat'] }}</p>
            <p class="font-mono-num mt-1 text-[11px] font-medium text-slate-400">{{ $summary['menit_telat'] }} menit total</p>
        </div>
        <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pulang Cepat</p>
            <p class="font-mono-num mt-2 text-3xl font-extrabold tracking-tight text-amber-600">{{ $summary['pulang'] }}</p>
            <p class="mt-1 text-[11px] font-medium text-slate-400">kali dalam {{ $year }}</p>
        </div>
    </div>

    {{-- Grafik --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div class="apple-glass-card rounded-3xl p-6 lg:col-span-7">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Kehadiran per Bulan</h2>
            <div style="position: relative; height: 260px; width: 100%;"><canvas id="performaBar"></canvas></div>
        </div>
        <div class="apple-glass-card rounded-3xl p-6 lg:col-span-5">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Akumulasi Menit Telat</h2>
            <div style="position: relative; height: 260px; width: 100%;"><canvas id="performaLine"></canvas></div>
        </div>
    </div>

    {{-- Tabel bulanan --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="border-b border-slate-100 px-6 py-4"><h2 class="text-sm font-bold text-slate-900">Rincian Bulanan</h2></div>
        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3">Bulan</th>
                        <th class="px-6 py-3 text-center">Hadir</th>
                        <th class="px-6 py-3 text-center">Izin/Cuti</th>
                        <th class="px-6 py-3 text-center">Telat</th>
                        <th class="px-6 py-3 text-center">Menit Telat</th>
                        <th class="px-6 py-3 text-center">Pulang Cepat</th>
                        <th class="px-6 py-3 text-center">Hari Kerja</th>
                        <th class="px-6 py-3 text-center">Ketepatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($monthly as $m => $d)
                        @php $tepat = $d['hadir'] > 0 ? round(($d['hadir'] - $d['telat']) / $d['hadir'] * 100, 1) : 0; @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-3 font-semibold text-slate-700">{{ $monthLabels[$m - 1] }}</td>
                            <td class="px-6 py-3 text-center font-mono-num">{{ $d['hadir'] }}</td>
                            <td class="px-6 py-3 text-center font-mono-num text-sky-600">{{ $d['izin'] }}</td>
                            <td class="px-6 py-3 text-center font-mono-num {{ $d['telat'] > 0 ? 'font-bold text-rose-600' : 'text-slate-400' }}">{{ $d['telat'] }}</td>
                            <td class="px-6 py-3 text-center font-mono-num">{{ $d['menit_telat'] }}</td>
                            <td class="px-6 py-3 text-center font-mono-num {{ $d['pulang'] > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ $d['pulang'] }}</td>
                            <td class="px-6 py-3 text-center font-mono-num text-slate-400">{{ $d['hari_kerja'] }}</td>
                            <td class="px-6 py-3 text-center font-mono-num font-bold text-slate-800">{{ $tepat }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-slate-100 bg-slate-50/60 text-[11px] font-bold text-slate-800">
                        <td class="px-6 py-3">TOTAL</td>
                        <td class="px-6 py-3 text-center font-mono-num">{{ $summary['hadir'] }}</td>
                        <td class="px-6 py-3 text-center font-mono-num">{{ $summary['izin'] }}</td>
                        <td class="px-6 py-3 text-center font-mono-num">{{ $summary['telat'] }}</td>
                        <td class="px-6 py-3 text-center font-mono-num">{{ $summary['menit_telat'] }}</td>
                        <td class="px-6 py-3 text-center font-mono-num">{{ $summary['pulang'] }}</td>
                        <td class="px-6 py-3 text-center font-mono-num">{{ $summary['hari_kerja'] }}</td>
                        <td class="px-6 py-3 text-center font-mono-num">{{ $summary['persen_tepat'] }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            const labels = @json($monthLabels);
            const base = {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, font: { size: 10 } } } },
            };

            const bar = document.getElementById('performaBar');
            if (bar && window.Chart) new Chart(bar.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Hadir', data: @json($hadirData), backgroundColor: '#10b981', borderRadius: 6, stack: 's' },
                    { label: 'Telat', data: @json($telatData), backgroundColor: '#e11d48', borderRadius: 6, stack: 's' },
                    { label: 'Pulang Cepat', data: @json($pulangData), backgroundColor: '#f59e0b', borderRadius: 6, stack: 's' },
                ]},
                options: { ...base, scales: { x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: 'rgba(226,232,240,0.5)' } } } },
            });

            const line = document.getElementById('performaLine');
            if (line && window.Chart) new Chart(line.getContext('2d'), {
                type: 'line',
                data: { labels, datasets: [{ label: 'Menit Telat', data: @json($menitData), borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.15)', fill: true, tension: 0.35, pointRadius: 3, borderWidth: 2 }] },
                options: { ...base, scales: { x: { grid: { display: false }, ticks: { font: { size: 10 } } }, y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: 'rgba(226,232,240,0.5)' } } } },
            });
        });
    </script>
@endpush
