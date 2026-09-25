@extends('layouts.app')

@section('title', 'Kehadiran Hari Ini · Absensi')
@section('heading', 'Kehadiran Hari Ini')
@section('subheading', $date->translatedFormat('l, d F Y'))

@php
    $badge = fn (array $s) => match ($s['key']) {
        'hadir' => 'badge-hadir',
        'telat' => 'badge-telat',
        'pulang_cepat' => 'badge-pulang',
        default => 'badge-alpa',
    };
    $chipUrl = fn (?string $s) => route('kehadiran.index', array_filter([
        'tanggal' => $date->toDateString(),
        'departemen' => $dept,
        'status' => $s,
    ]));
@endphp

@section('actions')
    <label class="hidden items-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-[11px] font-semibold text-slate-600 shadow-sm sm:flex">
        <input type="checkbox" id="autoRefresh" class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20">
        Auto-refresh 60s
    </label>
    <a href="{{ route('rekap.harian', ['dari' => $date->toDateString(), 'sampai' => $date->toDateString()]) }}"
       class="rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
        Buka di Rekap Harian
    </a>
@endsection

@section('content')
    @if ($holiday)
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-xs font-medium text-indigo-700">
            <b>Hari Libur:</b> {{ $holiday->name }} — tidak dihitung sebagai alpa.
        </div>
    @elseif ($isWeekend)
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-medium text-slate-600">
            Akhir pekan — absensi tidak dihitung.
        </div>
    @endif

    {{-- Filter --}}
    <form method="GET" action="{{ route('kehadiran.index') }}" class="apple-glass-card flex flex-wrap items-end gap-3 rounded-3xl p-4">
        <div>
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Tanggal</label>
            <input type="date" name="tanggal" value="{{ $date->toDateString() }}"
                   class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
        </div>
        <div class="min-w-[11rem]">
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Departemen</label>
            <select name="departemen" class="w-full rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                <option value="">Semua</option>
                @foreach ($departments as $d)
                    <option value="{{ $d }}" @selected($dept === $d)>{{ $d }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-500 active:scale-95">Terapkan</button>
        <a href="{{ route('kehadiran.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Hari Ini</a>
    </form>

    {{-- Chip ringkas (klik untuk filter) --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Hadir', 'hadir', $counts['hadir'], 'badge-hadir'],
            ['Telat', 'telat', $counts['telat'], 'badge-telat'],
            ['Pulang Cepat', 'pulang', $counts['pulang'], 'badge-pulang'],
            ['Belum Absen', 'belum', $counts['belum'], 'badge-alpa'],
        ] as [$label, $key, $value, $cls])
            <a href="{{ $chipUrl($status === $key ? null : $key) }}"
               @class([
                   'apple-glass-card apple-interactive-card block rounded-3xl p-4 transition',
                   'ring-2 ring-indigo-500/30' => $status === $key,
               ])>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                <div class="mt-1.5 flex items-center justify-between">
                    <p class="font-mono-num text-2xl font-extrabold text-slate-900">{{ $value }}</p>
                    <span class="badge-status {{ $cls }}"><span class="dot"></span>{{ $status === $key ? 'aktif' : 'lihat' }}</span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Tabel --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-900">Daftar Pegawai</h2>
            <span class="rounded-full border border-slate-200/60 bg-slate-50 px-2.5 py-0.5 font-mono-num text-[10px] font-bold text-slate-500">{{ $rows->count() }} pegawai</span>
        </div>
        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3">Pegawai</th>
                        <th class="px-6 py-3">Jabatan / Departemen</th>
                        <th class="px-6 py-3 text-center">Masuk</th>
                        <th class="px-6 py-3 text-center">Pulang</th>
                        <th class="px-6 py-3 text-center">Durasi</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $row)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/70 bg-gradient-to-tr from-slate-100 to-slate-50 text-[10px] font-bold text-slate-600">
                                        {{ strtoupper(substr($row['employee']->name, 0, 2)) }}
                                    </div>
                                    <a href="{{ route('pegawai.show', $row['employee']) }}" class="font-semibold text-slate-800 hover:text-indigo-600">{{ $row['employee']->name }}</a>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-slate-500">{{ $row['employee']->position ?: ($row['employee']->department ?: '—') }}</td>
                            <td class="px-6 py-3.5 text-center font-mono-num">{{ $row['recap']?->check_in?->format('H:i') ?? '—' }}</td>
                            <td class="px-6 py-3.5 text-center font-mono-num">{{ $row['recap']?->check_out?->format('H:i') ?? '—' }}</td>
                            <td class="px-6 py-3.5 text-center font-mono-num text-slate-500">
                                {{ $row['duration'] !== null ? intdiv($row['duration'], 60).'j '.($row['duration'] % 60).'m' : '—' }}
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <span class="badge-status {{ $badge($row['status']) }}"><span class="dot"></span>{{ $row['status']['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-14 text-center text-xs font-medium text-slate-400">Tidak ada pegawai sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const box = document.getElementById('autoRefresh');
            if (!box) return;
            box.checked = localStorage.getItem('absensi_autorefresh') === '1';
            box.addEventListener('change', () => localStorage.setItem('absensi_autorefresh', box.checked ? '1' : '0'));
            if (box.checked) setTimeout(() => window.location.reload(), 60000);
        })();
    </script>
@endpush
