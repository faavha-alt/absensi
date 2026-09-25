<header class="mb-5 flex h-16 shrink-0 select-none items-center justify-between gap-4 rounded-none px-6 apple-glass-card lg:rounded-3xl">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-sm font-extrabold tracking-tight text-slate-900">@yield('heading', 'Dashboard')</h1>
            <p class="truncate text-[11px] font-medium text-slate-400">@yield('subheading', 'Sistem Absensi Pegawai')</p>
        </div>
    </div>

    <div class="flex items-center gap-3">
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
