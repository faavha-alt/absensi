@extends('layouts.app')

@php
    $isEdit = $employee->exists;
@endphp

@section('title', ($isEdit ? 'Edit' : 'Tambah').' Pegawai · Absensi')
@section('heading', ($isEdit ? 'Edit' : 'Tambah').' Pegawai')
@section('subheading', $isEdit ? $employee->name : 'Daftarkan pegawai baru ke sistem absensi')

@section('content')
    <div class="mx-auto max-w-3xl">
        <form method="POST"
              action="{{ $isEdit ? route('pegawai.update', $employee) : route('pegawai.store') }}"
              class="space-y-5">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">
                    <ul class="space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="apple-glass-card rounded-3xl p-6">
                <h2 class="mb-5 text-sm font-bold text-slate-900">Identitas Pegawai</h2>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $employee->name) }}" required
                               class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">NIP / Kode Pegawai</label>
                        <input type="text" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" required
                               class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 font-mono-num text-sm text-slate-900 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">PIN Mesin Absensi</label>
                        <input type="text" name="zkteco_pin" value="{{ old('zkteco_pin', $employee->zkteco_pin) }}"
                               placeholder="mis. 12"
                               class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 font-mono-num text-sm text-slate-900 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                        <p class="mt-1.5 text-[10px] font-medium text-slate-400">Harus sama dengan PIN pegawai di mesin MB20. Jika diubah, jalankan “Hitung ulang rekap”.</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Jabatan</label>
                        <input type="text" name="position" value="{{ old('position', $employee->position) }}"
                               class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-400">Departemen / Unit</label>
                        <input type="text" name="department" value="{{ old('department', $employee->department) }}"
                               class="w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm text-slate-900 transition focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-500/5">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white/60 px-4 py-3">
                            <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $employee->is_admin))
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20">
                            <span>
                                <span class="block text-xs font-bold text-slate-800">Admin perangkat (tidak dihitung)</span>
                                <span class="block text-[10px] font-medium text-slate-400">Aktifkan jika akun ini milik pengelola mesin MB20, bukan pegawai yang absen. Kehadirannya tidak akan direkap.</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ $isEdit ? route('pegawai.show', $employee) : route('pegawai.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Batal</a>
                <button type="submit"
                        class="rounded-2xl bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white shadow-md shadow-slate-900/10 transition hover:bg-slate-800 active:scale-95">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pegawai' }}
                </button>
            </div>
        </form>
    </div>
@endsection
