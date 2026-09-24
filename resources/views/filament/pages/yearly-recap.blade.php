<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    @php
        $s = $this->getSummary();
        $rows = $this->getRows();
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(47,179,128,0.12); color:#2fb380;">
                <x-heroicon-o-check-circle />
            </div>
            <div>
                <p class="tailadmin-stat-label">Total Kehadiran</p>
                <p class="tailadmin-stat-value">{{ number_format($s['hadir'], 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(220,53,69,0.12); color:#dc3545;">
                <x-heroicon-o-clock />
            </div>
            <div>
                <p class="tailadmin-stat-label">Total Kali Telat</p>
                <p class="tailadmin-stat-value">{{ number_format($s['telat'], 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(245,158,11,0.14); color:#f59e0b;">
                <x-heroicon-o-arrow-left-start-on-rectangle />
            </div>
            <div>
                <p class="tailadmin-stat-label">Total Pulang Cepat</p>
                <p class="tailadmin-stat-value">{{ number_format($s['pulang'], 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(79,70,229,0.12); color:#4f46e5;">
                <x-heroicon-o-arrow-trending-down />
            </div>
            <div>
                <p class="tailadmin-stat-label">Total Menit Telat</p>
                <p class="tailadmin-stat-value">{{ number_format($s['menit_telat'], 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <x-filament::section heading="Rekap Bulanan {{ $this->getYear() }} (semua pegawai)">
        <x-attendance-chart :config="$this->barConfig()" :height="300" />
    </x-filament::section>

    <x-filament::section heading="Tren Menit Telat per Bulan">
        <x-attendance-chart :config="$this->lineConfig()" :height="240" />
    </x-filament::section>

    <x-filament::section heading="Peringkat Pegawai (paling tepat waktu di atas)">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-white/10">
                        <th class="px-3 py-2.5 font-semibold text-gray-500 dark:text-gray-400">#</th>
                        <th class="px-3 py-2.5 font-semibold text-gray-500 dark:text-gray-400">Pegawai</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">Hadir</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">Telat</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">Pulang Cepat</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">Menit Telat</th>
                        <th class="px-3 py-2.5 text-center font-semibold text-gray-500 dark:text-gray-400">Ketepatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($rows as $i => $row)
                        @php
                            $hadir = (int) $row->hadir;
                            $tepat = $hadir > 0 ? round(($hadir - (int) $row->telat) / $hadir * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td class="px-3 py-2.5 text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-2.5 font-medium">{{ $row->employee?->name ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-center">{{ $hadir }}</td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="st-badge {{ (int) $row->telat > 0 ? 'st-telat' : 'st-hadir' }}">{{ (int) $row->telat }}x</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="st-badge {{ (int) $row->pulang > 0 ? 'st-pulang' : 'st-hadir' }}">{{ (int) $row->pulang }}x</span>
                            </td>
                            <td class="px-3 py-2.5 text-center">{{ (int) $row->menit_telat }}</td>
                            <td class="px-3 py-2.5 text-center font-semibold">{{ $tepat }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500">Belum ada data untuk tahun ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
