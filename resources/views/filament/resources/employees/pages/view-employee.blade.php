<x-filament-panels::page>
    {{ $this->infolist }}

    @php
        $cal = $this->getCalendar();
        $firstDow = $cal[0]['date']->dayOfWeekIso; // 1=Sen .. 7=Min
        $todayDay = now()->day;
    @endphp

    <x-filament::section heading="Kalender Kehadiran · {{ now()->format('F Y') }}">
        <div class="mb-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-600 dark:text-gray-300">
            <span><span class="st-badge st-hadir">H</span> Hadir</span>
            <span><span class="st-badge st-telat">T</span> Telat</span>
            <span><span class="st-badge st-pulang">P</span> Pulang Cepat</span>
            <span><span class="st-badge st-alpa">A</span> Alpa</span>
            <span><span class="st-badge st-libur">L</span> Libur</span>
        </div>

        <div class="grid grid-cols-7 gap-2">
            @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $w)
                <div class="pb-1 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $w }}</div>
            @endforeach

            @for ($i = 1; $i < $firstDow; $i++)
                <div></div>
            @endfor

            @foreach ($cal as $cell)
                <div class="cal-cell {{ $cell['status']['c'] }} {{ $cell['day'] === $todayDay ? 'cal-today' : '' }}"
                     @if ($cell['status']['title']) title="{{ $cell['date']->format('d M Y') }} · {{ $cell['status']['title'] }}" @endif>
                    <span class="cal-num">{{ $cell['day'] }}</span>
                    <span class="cal-dot"></span>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
