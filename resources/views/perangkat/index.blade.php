@extends('layouts.app')

@section('title', 'Perangkat · Absensi')
@section('heading', 'Perangkat')
@section('subheading', $devices->count().' perangkat ZKTeco terdaftar')

@section('content')
    {{-- KPI --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @php
            $online = $devices->where('status', 'online')->count();
        @endphp
        @foreach ([
            ['Perangkat Online', $online.' / '.$devices->count(), $online > 0 ? 'text-emerald-600' : 'text-rose-600'],
            ['Scan Hari Ini', $todayScans, 'text-slate-900'],
            ['Total Scan Tersimpan', $totalScans, 'text-slate-900'],
            ['Scan Terakhir', $lastScan ? \Illuminate\Support\Carbon::parse($lastScan)->diffForHumans() : '—', 'text-indigo-600'],
        ] as [$label, $value, $color])
            <div class="apple-glass-card rounded-3xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                <p class="font-mono-num mt-1.5 text-xl font-extrabold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Kartu device --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($devices as $device)
            @php $isOnline = $device->status === 'online'; @endphp
            <div class="apple-glass-card apple-interactive-card rounded-3xl p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200/70 bg-slate-50">
                            <x-app-icon name="device" class="h-5 w-5 text-slate-500" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">{{ $device->name ?: 'Perangkat ZKTeco' }}</p>
                            <p class="font-mono-num text-[10px] font-medium text-slate-400">{{ $device->serial_number }}</p>
                        </div>
                    </div>
                    <span class="badge-status {{ $isOnline ? 'badge-hadir' : 'badge-telat' }}">
                        <span class="dot"></span>{{ $isOnline ? 'Online' : 'Offline' }}
                    </span>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-x-3 gap-y-2 text-[10px]">
                    <div><dt class="text-slate-400">Model</dt><dd class="font-mono-num font-semibold text-slate-700">{{ $device->model ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">IP</dt><dd class="font-mono-num font-semibold text-slate-700">{{ $device->ip_address ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">Firmware</dt><dd class="font-mono-num font-semibold text-slate-700">{{ $device->firmware_version ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">Push Ver.</dt><dd class="font-mono-num font-semibold text-slate-700">{{ $device->push_version ?: '—' }}</dd></div>
                </dl>

                <p class="mt-3 border-t border-slate-100 pt-3 font-mono-num text-[10px] text-slate-400">
                    Aktivitas terakhir: {{ $device->last_activity_at?->diffForHumans() ?? '—' }}
                </p>
            </div>
        @empty
            <div class="apple-glass-card rounded-3xl p-8 text-center md:col-span-2 xl:col-span-3">
                <p class="text-xs font-semibold text-slate-500">Belum ada perangkat terdaftar.</p>
            </div>
        @endforelse
    </div>

    {{-- Log scan mentah --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-900">Log Scan Mentah (30 terakhir)</h2>
            <span class="rounded-full border border-indigo-100/80 bg-indigo-50 px-2.5 py-0.5 font-mono-num text-[10px] font-bold text-indigo-600">dari perangkat</span>
        </div>
        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3">Waktu</th>
                        <th class="px-6 py-3">PIN</th>
                        <th class="px-6 py-3">Pegawai</th>
                        <th class="px-6 py-3 text-center">Verifikasi</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($recent as $log)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-3 font-mono-num">{{ $log->punched_at?->translatedFormat('d M Y · H:i:s') }}</td>
                            <td class="px-6 py-3 font-mono-num">{{ $log->pin }}</td>
                            <td class="px-6 py-3 font-semibold text-slate-700">{{ $pinToName[$log->pin] ?? '—' }}</td>
                            <td class="px-6 py-3 text-center font-mono-num text-slate-500">{{ $log->verify_type ?? '—' }}</td>
                            <td class="px-6 py-3 text-center font-mono-num text-slate-500">{{ $log->status ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-xs font-medium text-slate-400">Belum ada log scan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
