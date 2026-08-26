<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    @php
        $rows = $this->getRows();
        $totalHadir = $rows->sum('total_hadir');
        $totalTelat = $rows->sum('jumlah_telat');
        $totalMenitTelat = $rows->sum('total_menit_telat');
        $totalPulangCepat = $rows->sum('jumlah_pulang_cepat');
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(70, 95, 255, 0.1); color: #465fff;">
                <x-heroicon-o-users />
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Kehadiran</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $totalHadir }}</p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(240, 68, 56, 0.1); color: #f04438;">
                <x-heroicon-o-clock />
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Kali Telat</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $totalTelat }}</p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(247, 144, 9, 0.1); color: #f79009;">
                <x-heroicon-o-arrow-trending-down />
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Menit Telat</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $totalMenitTelat }}</p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(18, 183, 106, 0.1); color: #12b76a;">
                <x-heroicon-o-arrow-left-start-on-rectangle />
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Pulang Cepat</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $totalPulangCepat }}</p>
            </div>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            Rincian per Pegawai
        </x-slot>

        <div class="fi-ta-ctn overflow-x-auto">
            <table class="fi-ta-table w-full text-start">
                <thead class="divide-y divide-gray-200 dark:divide-white/5">
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Pegawai</th>
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Jumlah Hadir</th>
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Jumlah Telat</th>
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Total Menit Telat</th>
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Jumlah Pulang Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse ($rows as $row)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">{{ $row->employee?->name ?? '-' }}</td>
                            <td class="fi-ta-cell px-3 py-4 text-sm">{{ $row->total_hadir }}</td>
                            <td class="fi-ta-cell px-3 py-4 text-sm">
                                <x-filament::badge :color="$row->jumlah_telat > 0 ? 'danger' : 'success'">
                                    {{ $row->jumlah_telat }}x
                                </x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-sm">{{ $row->total_menit_telat }} menit</td>
                            <td class="fi-ta-cell px-3 py-4 text-sm">
                                <x-filament::badge :color="$row->jumlah_pulang_cepat > 0 ? 'warning' : 'success'">
                                    {{ $row->jumlah_pulang_cepat }}x
                                </x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">Belum ada data untuk bulan ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
