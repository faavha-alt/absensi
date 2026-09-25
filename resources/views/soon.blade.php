@extends('layouts.app')

@section('title', $title ?? 'Segera Hadir')
@section('heading', $title ?? 'Segera Hadir')
@section('subheading', $phase ?? '')

@section('content')
    <div class="apple-glass-card flex flex-col items-center justify-center rounded-3xl px-6 py-20 text-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-3xl border border-indigo-100 bg-indigo-50 text-indigo-600">
            <x-app-icon name="trending" class="h-6 w-6" />
        </div>
        <h2 class="mt-4 text-base font-extrabold tracking-tight text-slate-900">{{ $title ?? 'Modul ini segera hadir' }}</h2>
        <p class="mt-1 max-w-md text-xs font-medium text-slate-400">
            {{ $description ?? 'Halaman ini sedang dalam tahap pembangunan sesuai roadmap tata ulang UI/UX (lihat PLAN.md).' }}
        </p>
        <a href="{{ route('dashboard') }}"
           class="mt-6 rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
            ← Kembali ke Dashboard
        </a>
    </div>
@endsection
