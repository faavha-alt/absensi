@extends('layouts.app')

@section('title', 'Izin & Cuti · Absensi')
@section('heading', 'Izin, Sakit, Cuti & Dinas Luar')
@section('subheading', 'Dicatat manual oleh admin · '.$tahun)

@section('content')
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">
            @foreach ($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach ([
            ['Total Catatan', $summary['total'], 'badge-alpa'],
            ['Izin', $summary['izin'], 'badge-izin'],
            ['Sakit', $summary['sakit'], 'badge-sakit'],
            ['Cuti', $summary['cuti'], 'badge-cuti'],
            ['Dinas Luar', $summary['dinas'], 'badge-dinas'],
        ] as [$label, $value, $cls])
            <div class="apple-glass-card rounded-3xl p-4">
                <span class="badge-status {{ $cls }}"><span class="dot"></span>{{ $label }}</span>
                <p class="font-mono-num mt-2 text-2xl font-extrabold text-slate-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {{-- Form --}}
        <form method="POST"
              action="{{ $editing ? route('izin.update', $editing) : route('izin.store') }}"
              class="apple-glass-card h-fit rounded-3xl p-6 lg:col-span-4">
            @csrf
            @if ($editing) @method('PUT') @endif

            <h2 class="mb-5 text-sm font-bold text-slate-900">{{ $editing ? 'Edit Catatan' : 'Catat Izin / Cuti' }}</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Pegawai</label>
                    <select name="employee_id" required class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                        <option value="">— pilih pegawai —</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" @selected((int) old('employee_id', $editing?->employee_id) === $emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Jenis</label>
                    <select name="type" required class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $editing?->type) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Mulai</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $editing?->start_date?->toDateString()) }}" required
                               class="w-full rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Selesai</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $editing?->end_date?->toDateString()) }}" required
                               class="w-full rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Keterangan</label>
                    <input type="text" name="note" value="{{ old('note', $editing?->note) }}" maxlength="180" placeholder="mis. surat dokter / no. SK"
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
            </div>

            <div class="mt-5 flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-2xl bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
                    {{ $editing ? 'Simpan Perubahan' : 'Simpan' }}
                </button>
                @if ($editing)
                    <a href="{{ route('izin.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Batal</a>
                @endif
            </div>

            <p class="mt-3 text-[10px] font-medium leading-relaxed text-slate-400">
                Hari yang tercakup izin/cuti <b>tidak dihitung Alpa</b>. Rekap dihitung ulang otomatis.
            </p>
        </form>

        {{-- Daftar --}}
        <div class="apple-glass-card overflow-hidden rounded-3xl lg:col-span-8">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-bold text-slate-900">Daftar Catatan</h2>
                <form method="GET" action="{{ route('izin.index') }}" class="flex items-center gap-2">
                    <select name="pegawai" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600">
                        <option value="">Semua pegawai</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" @selected((int) $pegawaiId === $emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                    <select name="jenis" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600">
                        <option value="">Semua jenis</option>
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected($jenis === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="tahun" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-mono-num text-xs text-slate-600">
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" @selected($tahun === (int) $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="custom-scroll overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead>
                        <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-6 py-3">Pegawai</th>
                            <th class="px-6 py-3">Jenis</th>
                            <th class="px-6 py-3">Periode</th>
                            <th class="px-6 py-3 text-center">Hari</th>
                            <th class="px-6 py-3">Keterangan</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($leaves as $leave)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $leave->employee?->name ?? '-' }}</td>
                                <td class="px-6 py-3.5"><span class="badge-status {{ $leave->badgeClass() }}"><span class="dot"></span>{{ $leave->label() }}</span></td>
                                <td class="px-6 py-3.5 font-mono-num text-slate-500">
                                    {{ $leave->start_date->format('d M Y') }}@if (! $leave->start_date->equalTo($leave->end_date)) – {{ $leave->end_date->format('d M Y') }}@endif
                                </td>
                                <td class="px-6 py-3.5 text-center font-mono-num">{{ $leave->dayCount() }}</td>
                                <td class="px-6 py-3.5 text-slate-500">{{ $leave->note ?: '—' }}</td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('izin.index', ['edit' => $leave->id, 'tahun' => $tahun]) }}"
                                           class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-600 transition hover:bg-slate-50">Edit</a>
                                        <form method="POST" action="{{ route('izin.destroy', $leave) }}"
                                              onsubmit="return confirm('Hapus catatan {{ $leave->label() }} untuk {{ $leave->employee?->name }}? Rekap akan dihitung ulang.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-xl border border-rose-100 bg-rose-50 px-3 py-1.5 font-semibold text-rose-600 transition hover:bg-rose-100">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-14 text-center text-xs font-medium text-slate-400">Belum ada catatan izin/cuti untuk filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leaves->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">{{ $leaves->links() }}</div>
            @endif
        </div>
    </div>
@endsection
