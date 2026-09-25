@extends('layouts.app')

@section('title', 'Rekap Bulanan · Absensi')
@section('heading', 'Rekap Bulanan')
@section('subheading', $month->translatedFormat('F Y').' · '.$matrix['employees']->count().' pegawai')

@php
    $bulanNama = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
@endphp

@section('actions')
    <a href="{{ route('rekap.bulanan.export', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
       class="rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
        Export Excel
    </a>
@endsection

@section('content')
    {{-- Filter --}}
    <form method="GET" action="{{ route('rekap.bulanan') }}" class="apple-glass-card flex flex-wrap items-end gap-3 rounded-3xl p-4">
        <div>
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Bulan</label>
            <select name="bulan" class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                @foreach ($bulanNama as $num => $nama)
                    <option value="{{ $num }}" @selected($bulan === $num)>{{ $nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Tahun</label>
            <select name="tahun" class="rounded-2xl border border-slate-200 bg-white/80 px-3 py-2.5 font-mono-num text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                @foreach ($availableYears as $y)
                    <option value="{{ $y }}" @selected($tahun === $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-indigo-600/20 transition hover:bg-indigo-500 active:scale-95">Terapkan</button>
        <div class="ml-auto flex items-center gap-1">
            <a href="{{ route('rekap.bulanan', ['bulan' => $prevMonth->month, 'tahun' => $prevMonth->year]) }}"
               class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 font-mono-num text-xs text-slate-500 transition hover:bg-slate-50">‹ Bulan lalu</a>
            <a href="{{ route('rekap.bulanan', ['bulan' => $nextMonth->month, 'tahun' => $nextMonth->year]) }}"
               class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 font-mono-num text-xs text-slate-500 transition hover:bg-slate-50">Bulan depan ›</a>
        </div>
    </form>

    {{-- KPI --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Total Kehadiran', $totals['hadir'], 'text-slate-900'],
            ['Total Kali Telat', $totals['telat'], 'text-rose-600'],
            ['Total Pulang Cepat', $totals['pulang'], 'text-amber-600'],
            ['Total Menit Telat', $totals['menit_telat'], 'text-indigo-600'],
        ] as [$label, $value, $color])
            <div class="apple-glass-card rounded-3xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                <p class="font-mono-num mt-1.5 text-2xl font-extrabold {{ $color }}">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- Matriks --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-900">Matriks Harian</h2>
            <div class="flex flex-wrap gap-x-3 gap-y-1.5">
                <span class="badge-status badge-hadir">H · Hadir</span>
                <span class="badge-status badge-telat">T · Telat</span>
                <span class="badge-status badge-pulang">P · Pulang Cepat</span>
                <span class="badge-status badge-alpa">A · Alpa</span>
                <span class="badge-status badge-libur">L · Libur</span>
            </div>
        </div>

        <div class="custom-scroll overflow-x-auto">
            <table class="w-full text-xs text-slate-600">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="sticky left-0 bg-white px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Pegawai</th>
                        @foreach ($matrix['days'] as $date)
                            <th class="px-0.5 py-2 text-center">
                                <span class="st-date">{{ $date->format('d') }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($matrix['employees'] as $employee)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="sticky left-0 whitespace-nowrap bg-white px-4 py-2 text-left text-[11px] font-semibold text-slate-800">
                                {{ $employee->name }}
                            </td>
                            @foreach ($matrix['days'] as $date)
                                @php $cell = $matrix['cells'][$employee->id.'|'.$date->toDateString()] ?? ['c' => 'c-null', 'label' => '·', 'title' => '']; @endphp
                                <td class="px-0.5 py-1.5 text-center">
                                    <span class="st-cell {{ $cell['c'] }}" title="{{ $date->format('d M Y') }} · {{ $cell['title'] }}">{{ $cell['label'] }}</span>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td class="px-6 py-10 text-center text-xs text-slate-400">Belum ada pegawai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Ringkasan per pegawai --}}
    <div class="apple-glass-card overflow-hidden rounded-3xl">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-900">Ringkasan per Pegawai</h2>
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
                            <td class="px-6 py-3 text-slate-400">{{ $i + 1 }}</td>
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
                        <tr><td colspan="7" class="px-6 py-14 text-center text-xs font-medium text-slate-400">Belum ada data untuk bulan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
