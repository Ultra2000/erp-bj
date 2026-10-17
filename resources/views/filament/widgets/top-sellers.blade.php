<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-trophy class="w-5 h-5 text-amber-500" />
                <span>Classement des vendeurs — {{ $month }}</span>
            </div>
        </x-slot>

        @php
            $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' ' . $currency;
            $medals = ['🥇', '🥈', '🥉'];
        @endphp

        {{-- Plus grosse vente du mois --}}
        @if($biggest && $biggest['amount'] > 0)
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Plus grosse vente du mois</div>
                <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="text-2xl font-black text-amber-700 dark:text-amber-300">{{ $fmt($biggest['amount']) }}</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">par <strong>{{ $biggest['seller'] }}</strong></span>
                    <span class="text-xs text-gray-400">{{ $biggest['invoice'] }} — {{ $biggest['date'] }}</span>
                </div>
            </div>
        @endif

        @if(count($sellers))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-2">#</th>
                            <th class="py-2 pr-2">Vendeur</th>
                            <th class="py-2 pr-2 text-center">Ventes</th>
                            <th class="py-2 pr-2 text-right">CA du mois</th>
                            <th class="py-2 pr-2 text-right">Plus grosse vente</th>
                            <th class="py-2 text-right">Panier moyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sellers as $i => $s)
                            <tr @class([
                                'border-b border-gray-100 dark:border-white/5',
                                'bg-amber-50/50 dark:bg-amber-500/5' => $i === 0,
                            ])>
                                <td class="py-2 pr-2 font-bold">{{ $medals[$i] ?? ($i + 1) }}</td>
                                <td class="py-2 pr-2 font-semibold text-gray-800 dark:text-gray-100">{{ $s['name'] }}</td>
                                <td class="py-2 pr-2 text-center">{{ number_format($s['nb'], 0, ',', ' ') }}</td>
                                <td class="py-2 pr-2 text-right font-bold text-primary-600 dark:text-primary-400">{{ $fmt($s['ca']) }}</td>
                                <td class="py-2 pr-2 text-right">{{ $fmt($s['max_sale']) }}</td>
                                <td class="py-2 text-right text-gray-500 dark:text-gray-400">{{ $fmt($s['avg']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune vente attribuée à un vendeur ce mois-ci.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
