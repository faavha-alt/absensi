@extends('layouts.app')

@section('title', 'Data Pegawai · Absensi')
@section('heading', 'Data Pegawai')
@section('subheading', $employees->total().' pegawai terdaftar')

@php
    $badge = fn (array $s) => $s['class'] ?? 'badge-alpa';
@endphp

@section('actions')
    <a href="{{ route('pegawai.create') }}"
       class="rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
        + Tambah Pegawai
    </a>
@endsection

@section('content')
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('pegawai.index') }}" class="apple-glass-card flex flex-wrap items-end gap-3 rounded-3xl p-4">
        <div class="min-w-[12rem] flex-1">
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Cari</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Nama, NIP, atau PIN…"
                   class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
        </div>

        <div class="min-w-[10rem]">
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Departemen</label>
            <select name="departemen" class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                <option value="">Semua</option>
                @foreach ($departments as $d)
                    <option value="{{ $d }}" @selected($dept === $d)>{{ $d }}</option>
                @endforeach
            </select>
        </div>

        <div class="min-w-[9rem]">
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</label>
            <select name="status" class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                <option value="">Semua</option>
                <option value="pegawai" @selected($status === 'pegawai')>Pegawai</option>
                <option value="admin" @selected($status === 'admin')>Admin device</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-500 active:scale-95">
                Terapkan
            </button>
            <a href="{{ route('pegawai.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                Reset
            </a>
        </div>
    </form>

    {{-- Tabel --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-3">Pegawai</th>
                        <th class="px-6 py-3">NIP</th>
                        <th class="px-6 py-3">Jabatan</th>
                        <th class="px-6 py-3">Departemen</th>
                        <th class="px-6 py-3 text-center">PIN</th>
                        <th class="px-6 py-3 text-center">Status Hari Ini</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($employees as $employee)
                        @php
                            $recap = $recapsToday->get($employee->id);
                            $st = $employee->is_admin
                                ? ['key' => 'admin', 'label' => 'Admin device', 'class' => 'badge-alpa']
                                : ($recap ? $recap->status() : ['key' => 'belum', 'label' => 'Belum Absen', 'class' => 'badge-alpa']);
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-slate-200/70 bg-gradient-to-tr from-slate-100 to-slate-50 text-[10px] font-bold text-slate-600">
                                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                                    </div>
                                    <a href="{{ route('pegawai.show', $employee) }}" class="font-semibold text-slate-800 hover:text-indigo-600">
                                        {{ $employee->name }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 font-mono-num">{{ $employee->employee_code }}</td>
                            <td class="px-6 py-3.5 text-slate-500">{{ $employee->position ?: '—' }}</td>
                            <td class="px-6 py-3.5 text-slate-500">{{ $employee->department ?: '—' }}</td>
                            <td class="px-6 py-3.5 text-center font-mono-num">{{ $employee->zkteco_pin ?: '—' }}</td>
                            <td class="px-6 py-3.5 text-center">
                                @if ($employee->is_admin)
                                    <span class="badge-status badge-alpa"><span class="dot"></span>Admin device</span>
                                @else
                                    <span class="badge-status {{ $badge($st) }}"><span class="dot"></span>{{ $st['label'] }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('pegawai.show', $employee) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-600 transition hover:bg-slate-50">Detail</a>
                                    <a href="{{ route('pegawai.edit', $employee) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-600 transition hover:bg-slate-50">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <p class="text-xs font-semibold text-slate-500">Tidak ada pegawai yang cocok.</p>
                                <a href="{{ route('pegawai.create') }}" class="mt-2 inline-block text-xs font-semibold text-indigo-600 hover:underline">+ Tambah pegawai</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="border-t border-slate-100 px-6 py-4">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
@endsection
