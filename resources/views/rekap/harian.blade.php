@extends('layouts.app')

@section('title', 'Rekap Harian · Absensi')
@section('heading', 'Rekap Harian')
@section('subheading', $from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y'))

@php
    $badge = fn (array $s) => $s['class'] ?? 'badge-alpa';
@endphp

@section('actions')
    <a href="{{ route('rekap.harian.export', request()->query()) }}"
       class="rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
        Export Excel
    </a>
@endsection

@section('content')
    {{-- Filter --}}
    <form method="GET" action="{{ route('rekap.harian') }}" class="apple-glass-card flex flex-wrap items-end gap-3 rounded-3xl p-4">
        <div>
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Dari</label>
            <input type="date" name="dari" value="{{ $filters['dari'] }}"
                   class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
        </div>
        <div>
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Sampai</label>
            <input type="date" name="sampai" value="{{ $filters['sampai'] }}"
                   class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
        </div>
        <div class="min-w-[12rem]">
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Pegawai</label>
            <select name="pegawai" class="w-full rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                <option value="">Semua pegawai</option>
                @foreach ($employees as $id => $name)
                    <option value="{{ $id }}" @selected((int) $filters['pegawai'] === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</label>
            <select name="status" class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                <option value="">Semua</option>
                <option value="telat" @selected($filters['status'] === 'telat')>Hanya telat</option>
                <option value="pulang" @selected($filters['status'] === 'pulang')>Hanya pulang cepat</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-500 active:scale-95">Terapkan</button>
            <a href="{{ route('rekap.harian') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Reset</a>
        </div>
    </form>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Total Kehadiran', $summary['hadir'], 'text-slate-900'],
            ['Total Kali Telat', $summary['telat'], 'text-rose-600'],
            ['Total Pulang Cepat', $summary['pulang'], 'text-amber-600'],
            ['Total Menit Telat', $summary['menit_telat'], 'text-indigo-600'],
        ] as [$label, $value, $color])
            <div class="apple-glass-card rounded-3xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                <p class="font-mono-num mt-1.5 text-2xl font-extrabold {{ $color }}">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tabel --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Pegawai</th>
                        <th class="px-6 py-3 text-center">Masuk</th>
                        <th class="px-6 py-3 text-center">Pulang</th>
                        <th class="px-6 py-3 text-center">Telat (mnt)</th>
                        <th class="px-6 py-3 text-center">P. Cepat (mnt)</th>
                        <th class="px-6 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($rows as $row)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-3 font-mono-num">{{ $row->date->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ route('pegawai.show', $row->employee_id) }}" class="font-semibold text-slate-800 hover:text-indigo-600">
                                    {{ $row->employee?->name ?? '-' }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-center font-mono-num">{{ $row->check_in?->format('H:i') ?? '—' }}</td>
                            <td class="px-6 py-3 text-center font-mono-num">{{ $row->check_out?->format('H:i') ?? '—' }}</td>
                            <td class="px-6 py-3 text-center font-mono-num {{ $row->late_minutes > 0 ? 'font-bold text-rose-600' : 'text-slate-400' }}">{{ (int) $row->late_minutes }}</td>
                            <td class="px-6 py-3 text-center font-mono-num {{ $row->early_leave_minutes > 0 ? 'font-bold text-amber-600' : 'text-slate-400' }}">{{ (int) $row->early_leave_minutes }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="badge-status {{ $badge($row->status()) }}"><span class="dot"></span>{{ $row->status()['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-14 text-center text-xs font-medium text-slate-400">Tidak ada data pada rentang tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">{{ $rows->links() }}</div>
        @endif
    </div>
@endsection
