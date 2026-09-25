@php
    $nav = [
        'Pantau' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard'],
            ['label' => 'Kehadiran Hari Ini', 'route' => 'kehadiran.index', 'icon' => 'clock'],
            ['label' => 'Perangkat', 'route' => 'perangkat.index', 'icon' => 'device'],
        ],
        'Kepegawaian' => [
            ['label' => 'Data Pegawai', 'route' => 'pegawai.index', 'icon' => 'users'],
            ['label' => 'Izin & Cuti', 'route' => 'izin.index', 'icon' => 'flag'],
        ],
        'Rekap' => [
            ['label' => 'Rekap Harian', 'route' => 'rekap.harian', 'icon' => 'calendar-day'],
            ['label' => 'Rekap Bulanan', 'route' => 'rekap.bulanan', 'icon' => 'calendar'],
            ['label' => 'Rekap Tahunan', 'route' => 'rekap.tahunan', 'icon' => 'chart'],
        ],
        'Analitik' => [
            ['label' => 'Performa Pegawai', 'route' => 'performa.index', 'icon' => 'trending'],
        ],
        'Pengaturan' => [
            ['label' => 'Jam Kerja', 'route' => 'pengaturan.jam-kerja', 'icon' => 'cog'],
            ['label' => 'Hari Libur', 'route' => 'pengaturan.libur', 'icon' => 'flag'],
            ['label' => 'Akun & Keamanan', 'route' => 'pengaturan.akun', 'icon' => 'shield'],
        ],
    ];
@endphp

<aside class="app-sidebar flex w-64 shrink-0 select-none flex-col justify-between p-5">
    <div class="space-y-6">
        {{-- Brand --}}
        <div class="flex items-center justify-between gap-3 px-2">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-slate-900 via-slate-800 to-indigo-950 text-white shadow-md shadow-slate-900/15">
                    <x-app-icon name="fingerprint" class="h-5 w-5 text-indigo-400" />
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-extrabold tracking-tight text-slate-900">Absensi</span>
                        <span class="rounded border border-indigo-100/80 bg-indigo-50 px-1.5 py-0.5 text-[9px] font-extrabold uppercase text-indigo-600">Pro</span>
                    </div>
                    <p class="text-[11px] font-medium text-slate-400">Kepegawaian · MIPA UNS</p>
                </div>
            </div>
            <button type="button" data-nav-close class="rounded-xl p-1.5 text-lg leading-none text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden" title="Tutup menu" aria-label="Tutup menu navigasi">&times;</button>
        </div>
        </div>

        {{-- Status device --}}
        @php $online = \Syofyanzuhad\FilamentZktecoAdms\Models\Device::where('status', 'online')->count(); @endphp
        <div class="apple-glass-card rounded-2xl p-3">
            <div class="mb-1.5 flex items-center justify-between text-[11px] font-semibold text-slate-500">
                <span class="flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full {{ $online > 0 ? 'animate-pulse bg-emerald-500' : 'bg-rose-500' }}"></span>
                    Perangkat Alat
                </span>
                <span class="font-mono-num font-bold text-slate-900">{{ $online > 0 ? 'Online' : 'Offline' }}</span>
            </div>
            <p class="text-[10px] font-medium text-slate-400">{{ $online }} perangkat ZKTeco terhubung</p>
        </div>

        {{-- Navigasi --}}
        <nav class="space-y-5">
            @foreach ($nav as $group => $items)
                <div class="space-y-1">
                    <p class="mb-2 px-4 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $group }}</p>

                    @foreach ($items as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route']) }}"
                           @if ($active) aria-current="page" @endif
                           @class([
                               'flex items-center justify-between rounded-2xl px-3.5 py-2.5 text-xs font-semibold transition-all',
                               'bg-slate-900 text-white shadow-md shadow-slate-900/10' => $active,
                               'text-slate-600 hover:bg-white/60 hover:text-slate-900' => ! $active,
                           ])>
                            <span class="flex items-center gap-3">
                                <x-app-icon :name="$item['icon']" @class(['h-4 w-4', 'text-indigo-400' => $active, 'text-slate-400' => ! $active]) />
                                {{ $item['label'] }}
                            </span>
                            @if ($active)
                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                            @elseif (! empty($item['soon']))
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono-num text-[9px] font-bold text-slate-400">soon</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </div>

    {{-- Profil user --}}
    <div class="mt-6 flex items-center justify-between rounded-3xl border border-white/80 p-2.5 apple-glass-card">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-300/60 bg-gradient-to-tr from-slate-200 to-slate-100 text-xs font-bold text-slate-800 shadow-sm">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-xs font-bold text-slate-900">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="truncate font-mono-num text-[10px] font-medium text-slate-400">{{ auth()->user()->email ?? '' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Keluar" aria-label="Keluar dari akun" class="rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100/80 hover:text-slate-700">
                <x-app-icon name="logout" class="h-4 w-4" />
            </button>
        </form>
    </div>
</aside>
