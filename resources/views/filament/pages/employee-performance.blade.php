<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    @php
        $s = $this->getSummary();
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(47,179,128,0.12); color:#2fb380;">
                <x-heroicon-o-check-circle />
            </div>
            <div>
                <p class="tailadmin-stat-label">Total Kehadiran</p>
                <p class="tailadmin-stat-value">{{ $s['hadir'] }} <span class="text-sm font-normal text-gray-400">/ {{ $s['hari_kerja'] }} hari kerja</span></p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(220,53,69,0.12); color:#dc3545;">
                <x-heroicon-o-clock />
            </div>
            <div>
                <p class="tailadmin-stat-label">Total Telat</p>
                <p class="tailadmin-stat-value">{{ $s['telat'] }} <span class="text-sm font-normal text-gray-400">kali · {{ $s['menit_telat'] }} menit</span></p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(79,70,229,0.12); color:#4f46e5;">
                <x-heroicon-o-academic-cap />
            </div>
            <div>
                <p class="tailadmin-stat-label">Tingkat Kehadiran</p>
                <p class="tailadmin-stat-value">{{ $s['persen_hadir'] }}%</p>
            </div>
        </div>
        <div class="tailadmin-stat-card">
            <div class="tailadmin-stat-icon" style="background-color: rgba(245,158,11,0.14); color:#f59e0b;">
                <x-heroicon-o-arrow-left-start-on-rectangle />
            </div>
            <div>
                <p class="tailadmin-stat-label">Ketepatan Waktu</p>
                <p class="tailadmin-stat-value">{{ $s['persen_tepat'] }}%</p>
            </div>
        </div>
    </div>

    <x-filament::section heading="Kehadiran per Bulan — {{ $this->getEmployeeName() }}">
        <x-attendance-chart :config="$this->barConfig()" :height="300" />
    </x-filament::section>

    <x-filament::section heading="Akumulasi Menit Telat per Bulan">
        <x-attendance-chart :config="$this->lineConfig()" :height="260" />
    </x-filament::section>
</x-filament-panels::page>
