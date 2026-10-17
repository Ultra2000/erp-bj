<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-sparkles class="w-5 h-5 text-fuchsia-500" />
                <span>Clients ambassadeurs — {{ $year }}</span>
            </div>
        </x-slot>

        @php $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' ' . $currency; $medals = ['🥇', '🥈', '🥉']; @endphp

        @if(count($customers))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-2">#</th>
                            <th class="py-2 pr-2">Client</th>
                            <th class="py-2 pr-2 text-center">Achats</th>
                            <th class="py-2 text-right">Total dépensé</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $i => $c)
                            <tr @class([
                                'border-b border-gray-100 dark:border-white/5',
                                'bg-fuchsia-50/50 dark:bg-fuchsia-500/5' => $c['ambassador'],
                            ])>
                                <td class="py-2 pr-2 font-bold">{{ $medals[$i] ?? ($i + 1) }}</td>
                                <td class="py-2 pr-2">
                                    <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $c['name'] }}</span>
                                    @if($c['ambassador'])
                                        <span class="ml-2 rounded-full bg-fuchsia-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-fuchsia-700 dark:bg-fuchsia-500/20 dark:text-fuchsia-300">Ambassadeur</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-2 text-center">{{ number_format($c['nb'], 0, ',', ' ') }}</td>
                                <td class="py-2 text-right font-bold text-primary-600 dark:text-primary-400">{{ $fmt($c['ca']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucun achat client cette année.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
