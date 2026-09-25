<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F4F4F6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Absensi Pegawai')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="/js/chart.umd.min.js" defer></script>
    @stack('styles')
</head>
<body class="h-full flex overflow-hidden bg-[#F4F4F6] text-slate-900 selection:bg-indigo-100 selection:text-indigo-900">

    <div class="nav-backdrop" data-nav-close></div>

    @include('layouts.sidebar')

    <div class="flex min-w-0 flex-1 flex-col overflow-hidden py-0 pr-0 lg:py-5 lg:pr-5">
        @include('layouts.header')

        <main class="custom-scroll flex-1 space-y-5 overflow-y-auto pr-1">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
