@extends('layouts.app')

@section('title', 'Jam Kerja · Pengaturan')
@section('heading', 'Jam Kerja & Toleransi')
@section('subheading', 'Berlaku global untuk semua pegawai')

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
        <form method="POST" action="{{ route('pengaturan.jam-kerja.save') }}" class="apple-glass-card rounded-3xl p-6 lg:col-span-7">
            @csrf
            @method('PUT')

            <h2 class="mb-5 text-sm font-bold text-slate-900">Konfigurasi</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Jam Masuk</label>
                    <input type="time" name="time_in" value="{{ old('time_in', $schedule->time_in->format('H:i')) }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Toleransi Telat (menit)</label>
                    <input type="number" name="late_tolerance_minutes" min="0" max="180" value="{{ old('late_tolerance_minutes', $schedule->late_tolerance_minutes) }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Jam Pulang</label>
                    <input type="time" name="time_out" value="{{ old('time_out', $schedule->time_out->format('H:i')) }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Toleransi Pulang Cepat (menit)</label>
                    <input type="number" name="early_leave_tolerance_minutes" min="0" max="180" value="{{ old('early_leave_tolerance_minutes', $schedule->early_leave_tolerance_minutes) }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
            </div>

            <button type="submit"
                    class="mt-6 rounded-2xl bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95"
                    onclick="this.disabled=true; this.innerText='Menyimpan & menghitung ulang…'; this.form.submit();">
                Simpan & Hitung Ulang Rekap
            </button>
        </form>

        <div class="space-y-5 lg:col-span-5">
            <div class="apple-glass-card rounded-3xl p-6">
                <h3 class="text-sm font-bold text-slate-900">Bagaimana ini dipakai?</h3>
                <ul class="mt-3 space-y-2.5 text-[11px] font-medium leading-relaxed text-slate-500">
                    <li>• <b class="text-slate-700">Telat</b> dihitung jika scan masuk melewati <span class="font-mono-num">{{ $schedule->time_in->format('H:i') }}</span> + toleransi {{ $schedule->late_tolerance_minutes }} menit.</li>
                    <li>• <b class="text-slate-700">Pulang cepat</b> dihitung jika scan pulang lebih awal dari <span class="font-mono-num">{{ $schedule->time_out->format('H:i') }}</span> − toleransi {{ $schedule->early_leave_tolerance_minutes }} menit.</li>
                    <li>• Scan terakhir hanya dianggap pulang bila ≥ 12:00 atau ≥ 4 jam setelah masuk.</li>
                    <li>• Sabtu/Minggu dan tanggal di menu <b>Hari Libur</b> tidak dihitung.</li>
                    <li>• Pegawai bertanda <b>admin device</b> dikecualikan.</li>
                </ul>
            </div>

            <div class="apple-glass-card rounded-3xl p-6">
                <h3 class="text-sm font-bold text-slate-900">Efek menyimpan</h3>
                <p class="mt-2 text-[11px] font-medium leading-relaxed text-slate-500">
                    Seluruh rekap historis akan dihitung ulang memakai jam kerja ini. Proses berjalan beberapa detik; jangan tutup halaman sampai selesai.
                </p>
            </div>
        </div>
    </div>
@endsection
