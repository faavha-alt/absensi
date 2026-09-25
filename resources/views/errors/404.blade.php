<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F4F4F6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 · Halaman Tidak Ditemukan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-[#F4F4F6] p-6 text-slate-900">

    <div class="w-full max-w-md text-center">
        <div class="apple-glass-card rounded-3xl p-10">
            <p class="font-mono-num text-6xl font-extrabold tracking-tight text-indigo-600">404</p>
            <h1 class="mt-3 text-base font-extrabold tracking-tight text-slate-900">Halaman tidak ditemukan</h1>
            <p class="mt-1.5 text-xs font-medium leading-relaxed text-slate-400">
                Alamat yang Anda buka tidak tersedia atau sudah dipindahkan.
            </p>
            <a href="{{ url('/') }}"
               class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
                ← Kembali ke Dashboard
            </a>
        </div>
        <p class="mt-5 text-[10px] font-medium text-slate-400">Sistem Absensi Pegawai · MIPA UNS</p>
    </div>
</body>
</html>
