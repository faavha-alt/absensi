<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    <x-filament::section>
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
                    @forelse ($this->getRows() as $row)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell px-3 py-4 text-sm">{{ $row->employee?->name ?? '-' }}</td>
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
