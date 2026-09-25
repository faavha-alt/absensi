@extends('layouts.app')

@section('title', 'Rekap Tahunan · Absensi')
@section('heading', 'Rekap Tahunan')
@section('subheading', 'Agregat 12 bulan · '.$year)

@php
    $monthlyValues = array_values($monthly);
    $hadirData = array_map(fn ($m) => $m['hadir'], $monthlyValues);
    $telatData = array_map(fn ($m) => $m['telat'], $monthlyValues);
    $pulangData = array_map(fn ($m) => $m['pulang'], $monthlyValues);
    $menitData = array_map(fn ($m) => $m['menit_telat'], $monthlyValues);
    $totals = [
        'hadir' => array_sum($hadirData),
        'telat' => array_sum($telatData),
        'pulang' => array_sum($pulangData),
        'menit' => array_sum($menitData),
    ];
@endphp

@section('actions')
    <a href="{{ route('rekap.tahunan.export', ['tahun' => $year]) }}"
       class="rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
        Export Excel
    </a>
@endsection

@section('content')
    {{-- Filter --}}
    <form method="GET" action="{{ route('rekap.tahunan') }}" class="apple-glass-card flex flex-wrap items-end gap-3 rounded-3xl p-4">
        <div>
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Tahun</label>
            <select name="tahun" onchange="this.form.submit()" class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                @foreach ($availableYears as $y)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <p class="pb-2.5 text-[11px] font-medium text-slate-400">Menampilkan seluruh pegawai non-admin.</p>
    </form>

    {{-- KPI --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Total Kehadiran', $totals['hadir'], 'text-slate-900'],
            ['Total Kali Telat', $totals['telat'], 'text-rose-600'],
            ['Total Pulang Cepat', $totals['pulang'], 'text-amber-600'],
            ['Total Menit Telat', $totals['menit'], 'text-indigo-600'],
        ] as [$label, $value, $color])
            <div class="apple-glass-card rounded-3xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                <p class="font-mono-num mt-1.5 text-2xl font-extrabold {{ $color }}">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- Grafik --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div class="apple-glass-card rounded-3xl p-6 lg:col-span-7">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Rekap Bulanan {{ $year }}</h2>
            <div style="position: relative; height: 260px; width: 100%;"><canvas id="tahunanBar"></canvas></div>
        </div>
        <div class="apple-glass-card rounded-3xl p-6 lg:col-span-5">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Tren Menit Telat</h2>
            <div style="position: relative; height: 260px; width: 100%;"><canvas id="tahunanLine"></canvas></div>
        </div>
    </div>

    {{-- Peringkat --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-900">Peringkat Pegawai</h2>
            <span class="rounded-full border border-slate-200/60 bg-slate-50 px-2.5 py-0.5 text-[10px] font-bold text-slate-500">paling tepat waktu di atas</span>
        </div>
        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3">#</th>
                        <th class="px-6 py-3">Pegawai</th>
                        <th class="px-6 py-3 text-center">Hadir</th>
                        <th class="px-6 py-3 text-center">Telat</th>
                        <th class="px-6 py-3 text-center">Menit Telat</th>
                        <th class="px-6 py-3 text-center">Pulang Cepat</th>
                        <th class="px-6 py-3 text-center">Ketepatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $i => $row)
                        @php
                            $hadir = (int) $row->hadir;
                            $tepat = $hadir > 0 ? round(($hadir - (int) $row->telat) / $hadir * 100, 1) : 0;
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-3">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg font-mono-num text-[10px] font-bold {{ $i === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500' }}">{{ $i + 1 }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <a href="{{ route('pegawai.show', $row->employee_id) }}" class="font-semibold text-slate-800 hover:text-indigo-600">{{ $row->employee?->name ?? '-' }}</a>
                            </td>
                            <td class="px-6 py-3 text-center font-mono-num">{{ $hadir }}</td>
                            <td class="px-6 py-3 text-center"><span class="badge-status {{ (int) $row->telat > 0 ? 'badge-telat' : 'badge-hadir' }}">{{ (int) $row->telat }}x</span></td>
                            <td class="px-6 py-3 text-center font-mono-num">{{ (int) $row->menit_telat }}</td>
                            <td class="px-6 py-3 text-center"><span class="badge-status {{ (int) $row->pulang > 0 ? 'badge-pulang' : 'badge-hadir' }}">{{ (int) $row->pulang }}x</span></td>
                            <td class="px-6 py-3 text-center font-mono-num font-bold text-slate-800">{{ $tepat }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-14 text-center text-xs font-medium text-slate-400">Belum ada data untuk tahun ini.</td></tr>
                    @endforelse
                </tbody>
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

            const bar = document.getElementById('tahunanBar');
            if (bar && window.Chart) new Chart(bar.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Hadir', data: @json($hadirData), backgroundColor: '#10b981', borderRadius: 6 },
                    { label: 'Telat', data: @json($telatData), backgroundColor: '#e11d48', borderRadius: 6 },
                    { label: 'Pulang Cepat', data: @json($pulangData), backgroundColor: '#f59e0b', borderRadius: 6 },
                ]},
                options: { ...base, scales: { x: { grid: { display: false }, ticks: { font: { size: 10 } } }, y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: 'rgba(226,232,240,0.5)' } } } },
            });

            const line = document.getElementById('tahunanLine');
            if (line && window.Chart) new Chart(line.getContext('2d'), {
                type: 'line',
                data: { labels, datasets: [{ label: 'Menit Telat', data: @json($menitData), borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,0.15)', fill: true, tension: 0.35, pointRadius: 3, borderWidth: 2 }] },
                options: { ...base, scales: { x: { grid: { display: false }, ticks: { font: { size: 10 } } }, y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: 'rgba(226,232,240,0.5)' } } } },
            });
        });
    </script>
@endpush
