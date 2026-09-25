@extends('layouts.app')

@section('title', 'Dashboard · Absensi')
@section('heading', 'Dashboard')
@section('subheading', 'Ringkasan kehadiran pegawai hari ini')

@php
    $badge = fn (array $s) => match ($s['key']) {
        'hadir' => 'badge-hadir',
        'telat' => 'badge-telat',
        'pulang_cepat' => 'badge-pulang',
        'libur' => 'badge-libur',
        default => 'badge-alpa',
    };
@endphp

@section('content')
    {{-- KPI Bento --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="apple-glass-card apple-interactive-card flex flex-col justify-between rounded-3xl p-5">
            <div class="flex items-start justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pegawai Aktif</span>
                <span class="rounded-full border border-slate-100 bg-slate-50 px-2 py-0.5 font-mono-num text-[10px] font-bold text-slate-500">total</span>
            </div>
            <div class="mt-3">
                <p class="font-mono-num text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['pegawai'] }}</p>
                <p class="mt-1 text-[11px] font-medium text-slate-400">pegawai non-admin terdaftar</p>
            </div>
        </div>

        <div class="apple-glass-card apple-interactive-card flex flex-col justify-between rounded-3xl p-5">
            <div class="flex items-start justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Hadir Hari Ini</span>
                <span class="rounded-full border border-emerald-100 bg-emerald-50 px-2 py-0.5 font-mono-num text-[10px] font-bold text-emerald-600">
                    {{ $stats['pegawai'] > 0 ? round($stats['hadir'] / $stats['pegawai'] * 100) : 0 }}%
                </span>
            </div>
            <div class="mt-3">
                <p class="font-mono-num text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['hadir'] }}</p>
                <p class="mt-1 font-mono-num text-[11px] font-medium text-slate-400">/ {{ $stats['pegawai'] }} pegawai</p>
            </div>
        </div>

        <div class="apple-glass-card apple-interactive-card flex flex-col justify-between rounded-3xl p-5">
            <div class="flex items-start justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Telat Hari Ini</span>
                <span class="rounded-full border border-rose-100 bg-rose-50 px-2 py-0.5 font-mono-num text-[10px] font-bold text-rose-600">telat</span>
            </div>
            <div class="mt-3">
                <p class="font-mono-num text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['telat'] }}</p>
                <p class="mt-1 font-mono-num text-[11px] font-medium text-slate-400">{{ $stats['pulang'] }} pulang cepat</p>
            </div>
        </div>

        <div class="apple-glass-card apple-interactive-card flex flex-col justify-between rounded-3xl p-5">
            <div class="flex items-start justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Belum Absen</span>
                <span class="flex items-center gap-1.5 rounded-full border border-slate-200/60 bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                    <span class="h-1.5 w-1.5 rounded-full {{ $stats['device_online'] > 0 ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    <span class="font-mono-num">{{ $stats['device_online'] }}/{{ $stats['device_total'] }} device</span>
                </span>
            </div>
            <div class="mt-3">
                <p class="font-mono-num text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['belum'] }}</p>
                <p class="mt-1 text-[11px] font-medium text-slate-400">pegawai belum scan masuk</p>
            </div>
        </div>
    </div>

    {{-- Grafik + Scan terbaru --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div class="apple-glass-card rounded-3xl p-6 lg:col-span-8">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Tren Kehadiran</h2>
                    <p class="text-[11px] font-medium text-slate-400">{{ $range }} hari terakhir · seluruh pegawai</p>
                </div>
                <div class="flex items-center gap-1 rounded-2xl bg-slate-100/70 p-1 text-[11px] font-semibold">
                    @foreach ([7 => '7H', 30 => '30H', 90 => '90H'] as $r => $label)
                        <a href="{{ route('dashboard', ['range' => $r]) }}"
                           @class([
                               'rounded-xl px-3 py-1 font-mono-num transition-all',
                               'bg-white text-slate-900 shadow-sm' => $range === $r,
                               'text-slate-500 hover:text-slate-900' => $range !== $r,
                           ])>{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="trendChart"></canvas>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 text-center">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Rata Hadir/Hari</p>
                    <p class="font-mono-num mt-0.5 text-sm font-bold text-slate-800">{{ number_format(collect($trend['hadir'])->avg(), 1) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Rata Telat/Hari</p>
                    <p class="font-mono-num mt-0.5 text-sm font-bold text-rose-600">{{ number_format(collect($trend['telat'])->avg(), 1) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Scan</p>
                    <p class="font-mono-num mt-0.5 text-sm font-bold text-slate-800">{{ number_format(array_sum($trend['hadir'])) }}</p>
                </div>
            </div>
        </div>

        <div class="apple-glass-card rounded-3xl p-6 lg:col-span-4">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Scan Terbaru</h2>
                <span class="rounded-full border border-indigo-100/80 bg-indigo-50 px-2 py-0.5 font-mono-num text-[10px] font-bold text-indigo-600">realtime</span>
            </div>

            <div class="custom-scroll max-h-[22rem] space-y-1 overflow-y-auto pr-1">
                @forelse ($recent as $log)
                    <div class="flex items-center justify-between rounded-2xl px-3 py-2.5 transition hover:bg-slate-50">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/70 bg-slate-50 font-mono-num text-[10px] font-bold text-slate-500">
                                {{ $log->pin }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-slate-800">{{ $pinToName[$log->pin] ?? 'PIN '.$log->pin }}</p>
                                <p class="font-mono-num text-[10px] font-medium text-slate-400">{{ $log->punched_at?->translatedFormat('d M · H:i:s') }}</p>
                            </div>
                        </div>
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                    </div>
                @empty
                    <p class="px-3 py-8 text-center text-xs font-medium text-slate-400">Belum ada scan.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Kehadiran hari ini --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Kehadiran Hari Ini</h2>
                <p class="text-[11px] font-medium text-slate-400">{{ $today->translatedFormat('l, d F Y') }}</p>
            </div>
            <span class="rounded-full border border-slate-200/60 bg-slate-50 px-2.5 py-0.5 font-mono-num text-[10px] font-bold text-slate-500">
                {{ $stats['hadir'] }} hadir · {{ $stats['belum'] }} belum
            </span>
        </div>

        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3">Pegawai</th>
                        <th class="px-6 py-3">Jabatan</th>
                        <th class="px-6 py-3 text-center">Masuk</th>
                        <th class="px-6 py-3 text-center">Pulang</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($todayRows as $row)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/70 bg-gradient-to-tr from-slate-100 to-slate-50 text-[10px] font-bold text-slate-600">
                                        {{ strtoupper(substr($row['employee']->name, 0, 2)) }}
                                    </div>
                                    <span class="font-semibold text-slate-800">{{ $row['employee']->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">{{ $row['employee']->position ?: ($row['employee']->department ?: '-') }}</td>
                            <td class="px-6 py-3.5 text-center font-mono-num">
                                {{ $row['recap']?->check_in?->format('H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-3.5 text-center font-mono-num">
                                {{ $row['recap']?->check_out?->format('H:i') ?? '—' }}
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <span class="badge-status {{ $badge($row['status']) }}">
                                    <span class="dot"></span>{{ $row['status']['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-xs font-medium text-slate-400">Belum ada pegawai terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            const el = document.getElementById('trendChart');
            if (!el || !window.Chart) return;

            new Chart(el.getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($trend['labels']),
                    datasets: [
                        {
                            label: 'Hadir',
                            data: @json($trend['hadir']),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.12)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 0,
                            borderWidth: 2,
                        },
                        {
                            label: 'Telat',
                            data: @json($trend['telat']),
                            borderColor: '#e11d48',
                            backgroundColor: 'rgba(225,29,72,0.08)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 0,
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, font: { size: 10 } } } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: 'rgba(226,232,240,0.5)' } },
                        x: { ticks: { font: { size: 10 }, maxTicksLimit: 12 }, grid: { display: false } },
                    },
                },
            });
        });
    </script>
@endpush
