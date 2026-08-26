<x-filament-widgets::widget>
    <x-filament::section heading="Kehadiran Hari Ini">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 text-left">
                        <th class="px-3 py-2 font-semibold text-gray-500 dark:text-gray-400">Pegawai</th>
                        <th class="px-3 py-2 font-semibold text-gray-500 dark:text-gray-400">Jam Masuk</th>
                        <th class="px-3 py-2 font-semibold text-gray-500 dark:text-gray-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($this->getRows() as $row)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $row['employee'] }}</td>
                            <td class="px-3 py-2">
                                @if ($row['check_in'])
                                    {{ $row['check_in']->format('H:i') }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <x-attendance-status-badge :status="$row['status']" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                Belum ada data hari ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
