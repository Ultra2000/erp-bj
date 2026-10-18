<x-filament-panels::page>
    @php
        $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' ' . $currency;
        $medals = ['🥇', '🥈', '🥉'];
    @endphp

    {{-- Sélecteur de période --}}
    <x-filament::section>
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Du</label>
                <input type="date" wire:model.live="start_date"
                       class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Au</label>
                <input type="date" wire:model.live="end_date"
                       class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm">
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $clients_count }} client(s) &bull; {{ $total_sales }} vente(s) &bull; CA total : <strong>{{ $fmt($total_ca) }}</strong>
            </div>
        </div>
    </x-filament::section>

    {{-- Meilleure vente de la période --}}
    @if($biggest && $biggest['amount'] > 0)
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-500/20 dark:bg-indigo-500/10">
            <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Plus grosse vente de la période</div>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ $fmt($biggest['amount']) }}</span>
                <span class="text-sm text-gray-600 dark:text-gray-300">par <strong>{{ $biggest['client'] }}</strong></span>
                <span class="text-xs text-gray-400">{{ $biggest['invoice'] }} — {{ $biggest['date'] }}</span>
            </div>
        </div>
    @endif

    {{-- Classement --}}
    <x-filament::section>
        <x-slot name="heading">Classement des clients (par chiffre d'affaires)</x-slot>
        @if(count($customers))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-2">#</th>
                            <th class="py-2 pr-2">Client</th>
                            <th class="py-2 pr-2 text-center">Achats</th>
                            <th class="py-2 pr-2 text-right">CA</th>
                            <th class="py-2 pr-2 text-right">Plus gros achat</th>
                            <th class="py-2 pr-2 text-right">Panier moyen</th>
                            <th class="py-2 text-right">Dernier achat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $i => $c)
                            <tr @class([
                                'border-b border-gray-100 dark:border-white/5',
                                'bg-indigo-50/50 dark:bg-indigo-500/5' => $i === 0,
                            ])>
                                <td class="py-2 pr-2 font-bold">{{ $medals[$i] ?? ($i + 1) }}</td>
                                <td class="py-2 pr-2 font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $c['name'] }}
                                    @if($c['phone'])<div class="text-xs font-normal text-gray-400">{{ $c['phone'] }}</div>@endif
                                </td>
                                <td class="py-2 pr-2 text-center">{{ number_format($c['nb'], 0, ',', ' ') }}</td>
                                <td class="py-2 pr-2 text-right font-bold text-primary-600 dark:text-primary-400">{{ $fmt($c['ca']) }}</td>
                                <td class="py-2 pr-2 text-right">{{ $fmt($c['max_sale']) }}</td>
                                <td class="py-2 pr-2 text-right text-gray-500 dark:text-gray-400">{{ $fmt($c['avg']) }}</td>
                                <td class="py-2 text-right text-gray-500 dark:text-gray-400">{{ $c['last_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune vente client sur cette période.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
