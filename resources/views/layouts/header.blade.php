<header class="mb-5 flex h-16 shrink-0 select-none items-center justify-between gap-4 rounded-none px-6 apple-glass-card lg:rounded-3xl">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button type="button" data-nav-toggle
                class="rounded-2xl border border-slate-200/60 bg-white p-2.5 text-slate-600 shadow-sm transition hover:bg-slate-50 lg:hidden"
                title="Buka menu">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="min-w-0">
            <h1 class="truncate text-sm font-extrabold tracking-tight text-slate-900">@yield('heading', 'Dashboard')</h1>
            <p class="truncate text-[11px] font-medium text-slate-400">@yield('subheading', 'Sistem Absensi Pegawai')</p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        @yield('actions')

        <div class="hidden items-center gap-2 rounded-2xl border border-slate-200/60 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 sm:flex">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class="text-[11px] text-slate-400">Hari ini</span>
            <span class="font-mono-num font-bold text-slate-800">{{ now()->translatedFormat('d M Y') }}</span>
        </div>

        <button class="relative rounded-2xl border border-slate-200/60 bg-white p-2.5 text-slate-600 shadow-sm transition hover:bg-slate-50" title="Notifikasi">
            <x-app-icon name="bell" class="h-4 w-4" />
        </button>
    </div>
</header>
