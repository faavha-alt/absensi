<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F4F4F6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · Absensi Pegawai</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full items-center justify-center bg-[#F4F4F6] p-6 text-slate-900 selection:bg-indigo-100 selection:text-indigo-900">

    <div class="w-full max-w-sm">
        <div class="mb-6 flex flex-col items-center gap-3">
            <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-gradient-to-tr from-slate-900 via-slate-800 to-indigo-950 text-white shadow-lg shadow-slate-900/20">
                <x-app-icon name="fingerprint" class="h-7 w-7 text-indigo-400" />
            </div>
            <div class="text-center">
                <h1 class="text-lg font-extrabold tracking-tight text-slate-900">Absensi Pegawai</h1>
                <p class="text-xs font-medium text-slate-400">Kepegawaian · MIPA UNS</p>
            </div>
        </div>

        <div class="apple-glass-card rounded-3xl p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5"
                           placeholder="nama@email.com">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Kata Sandi</label>
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5"
                           placeholder="••••••••">
                </div>

                <label class="flex items-center gap-2 text-xs font-medium text-slate-500">
                    <input type="checkbox" name="remember" class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20">
                    Ingat saya
                </label>

                <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
                    Masuk
                </button>
            </form>
        </div>

        <p class="mt-5 text-center text-[10px] font-medium text-slate-400">Sistem Absensi Pegawai · ZKTeco ADMS</p>
    </div>
</body>
</html>
