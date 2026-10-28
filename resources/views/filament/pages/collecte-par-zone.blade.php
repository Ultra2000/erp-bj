<x-filament-panels::page>
    @php $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency; @endphp

    {{-- Cartes de synthèse --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total épargné</div>
            <div class="mt-1 text-2xl font-black text-primary-600 dark:text-primary-400">{{ $fmt($total_solde) }}</div>
            <div class="text-xs text-gray-400">{{ number_format($total_savers, 0, ',', ' ') }} épargnant(s)</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Collecté aujourd'hui</div>
            <div class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ $fmt($total_today) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Collecté — {{ $month_label }}</div>
            <div class="mt-1 text-2xl font-black">{{ $fmt($total_month) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Zones / Tontiniers</div>
            <div class="mt-1 text-2xl font-black">{{ $zones_count }} / {{ $agents_count }}</div>
        </x-filament::section>
    </div>

    {{-- Par zone --}}
    <x-filament::section>
        <x-slot name="heading">Par zone</x-slot>
        @if(count($zoneRows))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-2">Zone</th>
                            <th class="py-2 pr-2">Tontiniers</th>
                            <th class="py-2 pr-2 text-center">Épargnants</th>
                            <th class="py-2 pr-2 text-right">Total épargné</th>
                            <th class="py-2 pr-2 text-right">Aujourd'hui</th>
                            <th class="py-2 text-right">Ce mois</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($zoneRows as $z)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-2 font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $z['name'] }}
                                    @if($z['city'])<div class="text-xs font-normal text-gray-400">{{ $z['city'] }}</div>@endif
                                </td>
                                <td class="py-2 pr-2 text-gray-500 dark:text-gray-400">
                                    {{ count($z['tontiniers']) ? implode(', ', $z['tontiniers']) : '—' }}
                                </td>
                                <td class="py-2 pr-2 text-center">{{ number_format($z['savers'], 0, ',', ' ') }}</td>
                                <td class="py-2 pr-2 text-right font-bold text-primary-600 dark:text-primary-400">{{ $fmt($z['solde']) }}</td>
                                <td class="py-2 pr-2 text-right text-emerald-600 dark:text-emerald-400">{{ $fmt($z['today']) }}</td>
                                <td class="py-2 text-right">{{ $fmt($z['month']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune zone définie.</p>
        @endif
    </x-filament::section>

    {{-- Par tontinier --}}
    <x-filament::section>
        <x-slot name="heading">Par tontinier</x-slot>
        @if(count($agentRows))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-2">Tontinier</th>
                            <th class="py-2 pr-2">Zones</th>
                            <th class="py-2 pr-2 text-center">Dépôts (jour)</th>
                            <th class="py-2 pr-2 text-right">Aujourd'hui</th>
                            <th class="py-2 text-right">Ce mois</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agentRows as $a)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-2 font-semibold text-gray-800 dark:text-gray-100">{{ $a['name'] }}</td>
                                <td class="py-2 pr-2 text-gray-500 dark:text-gray-400">{{ count($a['zones']) ? implode(', ', $a['zones']) : '—' }}</td>
                                <td class="py-2 pr-2 text-center">{{ number_format($a['today_nb'], 0, ',', ' ') }}</td>
                                <td class="py-2 pr-2 text-right text-emerald-600 dark:text-emerald-400">{{ $fmt($a['today']) }}</td>
                                <td class="py-2 text-right font-bold text-primary-600 dark:text-primary-400">{{ $fmt($a['month']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucun tontinier enregistré.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
