@extends('layouts.app')

@section('title', 'Hari Libur · Pengaturan')
@section('heading', 'Hari Libur')
@section('subheading', 'Libur nasional & cuti bersama · '.$tahun)

@section('content')
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">
            @foreach ($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {{-- Form tambah --}}
        <form method="POST" action="{{ route('pengaturan.libur.store') }}" class="apple-glass-card h-fit rounded-3xl p-6 lg:col-span-4">
            @csrf
            <h2 class="mb-5 text-sm font-bold text-slate-900">Tambah Hari Libur</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Tanggal</label>
                    <input type="date" name="date" value="{{ old('date') }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Keterangan</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="mis. Hari Kemerdekaan RI"
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
            </div>

            <button type="submit"
                    class="mt-5 w-full rounded-2xl bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
                Simpan & Hitung Ulang
            </button>
            <p class="mt-3 text-[10px] font-medium leading-relaxed text-slate-400">
                Tanggal libur tidak dihitung sebagai alpa. Rekap dihitung ulang otomatis setiap perubahan.
            </p>
        </form>

        {{-- Daftar --}}
        <div class="apple-glass-card overflow-hidden rounded-3xl lg:col-span-8">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-bold text-slate-900">Daftar Libur {{ $tahun }}</h2>
                <form method="GET" action="{{ route('pengaturan.libur') }}">
                    <select name="tahun" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 font-mono-num text-xs text-slate-600">
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" @selected($tahun === (int) $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="custom-scroll max-h-[34rem] overflow-y-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="sticky top-0 bg-white">
                        <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Hari</th>
                            <th class="px-6 py-3">Keterangan</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($holidays as $holiday)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-6 py-3 font-mono-num font-semibold text-slate-700">{{ $holiday->date->format('d M Y') }}</td>
                                <td class="px-6 py-3 text-slate-500">{{ $holiday->date->translatedFormat('l') }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $holiday->name }}</td>
                                <td class="px-6 py-3 text-right">
                                    <form method="POST" action="{{ route('pengaturan.libur.destroy', $holiday) }}"
                                          onsubmit="return confirm('Hapus {{ $holiday->name }} ({{ $holiday->date->format('d M Y') }})? Rekap akan dihitung ulang.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-xl border border-rose-100 bg-rose-50 px-3 py-1.5 font-semibold text-rose-600 transition hover:bg-rose-100">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-14 text-center text-xs font-medium text-slate-400">Belum ada hari libur untuk {{ $tahun }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
