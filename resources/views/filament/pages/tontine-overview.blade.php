<x-filament-panels::page>
    @php
        $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency;
        $typeLabels = \App\Models\SavingsTransaction::TYPES;
    @endphp

    {{-- Cartes de synthèse --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total épargné (dû aux clients)</div>
            <div class="mt-1 text-2xl font-black text-primary-600 dark:text-primary-400">{{ $fmt($total) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Épargnants</div>
            <div class="mt-1 text-2xl font-black">{{ number_format($savers, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-400">Solde moyen : {{ $fmt($average) }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Mouvements — {{ $month }}</div>
            <div class="mt-1 space-y-0.5 text-sm">
                <div class="flex justify-between"><span class="text-emerald-600">Dépôts</span><span class="font-bold">+ {{ $fmt($deposits_month) }}</span></div>
                <div class="flex justify-between"><span class="text-red-600">Retraits</span><span class="font-bold">- {{ $fmt($withdrawals_month) }}</span></div>
                <div class="flex justify-between"><span class="text-amber-600">Utilisés (achats)</span><span class="font-bold">- {{ $fmt($used_month) }}</span></div>
            </div>
        </x-filament::section>
    </div>

    {{-- Top épargnants --}}
    <x-filament::section>
        <x-slot name="heading">Top épargnants</x-slot>
        @if($topSavers->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-2">#</th>
                            <th class="py-2 pr-2">Client</th>
                            <th class="py-2 pr-2">Téléphone</th>
                            <th class="py-2 text-right">Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topSavers as $i => $c)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-2 font-bold">{{ $i + 1 }}</td>
                                <td class="py-2 pr-2">
                                    <button type="button"
                                            wire:click="mountAction('viewMovements', { customer: {{ $c->id }} })"
                                            class="font-semibold text-primary-600 hover:underline dark:text-primary-400">{{ $c->name }}</button>
                                </td>
                                <td class="py-2 pr-2 text-gray-500">{{ $c->phone ?: '—' }}</td>
                                <td class="py-2 text-right font-bold">{{ $fmt($c->savings_balance) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucun client avec un solde d'épargne.</p>
        @endif
    </x-filament::section>

    {{-- Top tontiniers (collecte du mois) --}}
    <x-filament::section>
        <x-slot name="heading">Top tontiniers — {{ $month }}</x-slot>
        @if(count($topTontiniers))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-2">#</th>
                            <th class="py-2 pr-2">Tontinier</th>
                            <th class="py-2 pr-2 text-center">Dépôts</th>
                            <th class="py-2 text-right">Collecté</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $medals = ['🥇', '🥈', '🥉']; @endphp
                        @foreach($topTontiniers as $i => $t)
                            <tr @class([
                                'border-b border-gray-100 dark:border-white/5',
                                'bg-emerald-50/50 dark:bg-emerald-500/5' => $i === 0,
                            ])>
                                <td class="py-2 pr-2 font-bold">{{ $medals[$i] ?? ($i + 1) }}</td>
                                <td class="py-2 pr-2 font-semibold text-gray-800 dark:text-gray-100">{{ $t['name'] }}</td>
                                <td class="py-2 pr-2 text-center">{{ number_format($t['nb'], 0, ',', ' ') }}</td>
                                <td class="py-2 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ $fmt($t['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucune collecte de tontinier ce mois-ci.</p>
        @endif
    </x-filament::section>

    {{-- Derniers mouvements --}}
    <x-filament::section>
        <x-slot name="heading">Derniers mouvements</x-slot>
        @if($recent->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-2">Date</th>
                            <th class="py-2 pr-2">Client</th>
                            <th class="py-2 pr-2">Opération</th>
                            <th class="py-2 pr-2 text-right">Montant</th>
                            <th class="py-2 text-right">Solde après</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent as $t)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-2 whitespace-nowrap text-gray-500">@dt($t->created_at, 'd/m/Y H:i')</td>
                                <td class="py-2 pr-2">{{ $t->customer?->name ?? '—' }}</td>
                                <td class="py-2 pr-2">{{ $typeLabels[$t->type] ?? $t->type }}</td>
                                <td @class(['py-2 pr-2 text-right font-semibold', 'text-emerald-600' => $t->amount >= 0, 'text-red-600' => $t->amount < 0])>
                                    {{ ($t->amount >= 0 ? '+' : '') . number_format((int) $t->amount, 0, ',', ' ') }} {{ $currency }}
                                </td>
                                <td class="py-2 text-right">{{ $fmt($t->balance_after) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Aucun mouvement d'épargne pour le moment.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
