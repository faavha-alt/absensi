@extends('layouts.app')

@section('title', 'Akun & Keamanan · Pengaturan')
@section('heading', 'Akun & Keamanan')
@section('subheading', $users->count().' akun admin terdaftar')

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
        {{-- Tambah akun --}}
        <form method="POST" action="{{ route('pengaturan.akun.store') }}" class="apple-glass-card h-fit rounded-3xl p-6 lg:col-span-4">
            @csrf
            <h2 class="mb-5 text-sm font-bold text-slate-900">Tambah Akun Admin</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Kata Sandi</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Ulangi Kata Sandi</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                </div>
            </div>

            <button type="submit" class="mt-5 w-full rounded-2xl bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
                Tambah Akun
            </button>
        </form>

        <div class="space-y-5 lg:col-span-8">
            {{-- Daftar akun --}}
            <div class="apple-glass-card overflow-hidden rounded-3xl">
                <div class="border-b border-slate-100 px-6 py-4"><h2 class="text-sm font-bold text-slate-900">Daftar Akun</h2></div>
                <div class="divide-y divide-slate-50">
                    @foreach ($users as $user)
                        <div class="px-6 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-200/70 bg-gradient-to-tr from-slate-100 to-slate-50 text-[10px] font-bold text-slate-600">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-800">
                                            {{ $user->name }}
                                            @if (auth()->id() === $user->id)
                                                <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-indigo-600">Anda</span>
                                            @endif
                                        </p>
                                        <p class="font-mono-num text-[10px] font-medium text-slate-400">{{ $user->email }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <details class="relative">
                                        <summary class="cursor-pointer list-none rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50">Ganti Sandi</summary>
                                        <form method="POST" action="{{ route('pengaturan.akun.password', $user) }}" class="mt-2 flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="password" name="password" placeholder="Sandi baru" required minlength="8"
                                                   class="w-36 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] focus:border-indigo-300 focus:outline-none">
                                            <input type="password" name="password_confirmation" placeholder="Ulangi" required minlength="8"
                                                   class="w-32 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] focus:border-indigo-300 focus:outline-none">
                                            <button type="submit" class="rounded-xl bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-indigo-500">Simpan</button>
                                        </form>
                                    </details>

                                    @if (auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('pengaturan.akun.destroy', $user) }}"
                                              onsubmit="return confirm('Hapus akun {{ $user->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-xl border border-rose-100 bg-rose-50 px-3 py-1.5 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-100">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Keamanan endpoint --}}
            <div class="apple-glass-card rounded-3xl p-6">
                <h2 class="text-sm font-bold text-slate-900">Keamanan Endpoint Alat</h2>
                <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Auto-register device</dt>
                        <dd class="mt-1 text-xs font-semibold text-slate-700">{{ config('zkteco-adms.device.auto_register') ? 'Aktif (device baru diterima)' : 'Nonaktif (hanya SN terdaftar)' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Whitelist IP</dt>
                        <dd class="mt-1 text-xs font-semibold text-slate-700">{{ config('zkteco-adms.security.allowed_ips') ? 'Aktif' : 'Belum diaktifkan' }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Log audit ADMS</dt>
                        <dd class="mt-1 text-xs font-semibold text-slate-700">Channel <span class="font-mono-num">adms</span> · retensi 30 hari</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Endpoint alat</dt>
                        <dd class="mt-1 font-mono-num text-xs font-semibold text-emerald-600">/iclock/* aktif</dd>
                    </div>
                </dl>
                <p class="mt-4 text-[10px] font-medium leading-relaxed text-slate-400">
                    Peran/RBAC granular (Super Admin, Admin Kepegawaian, Pimpinan) direncanakan pada SPEC §1 namun belum diimplementasikan.
                </p>
            </div>
        </div>
    </div>
@endsection
