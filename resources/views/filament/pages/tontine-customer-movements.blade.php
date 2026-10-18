@php
    $fmt = fn ($v) => number_format((int) $v, 0, ',', ' ') . ' ' . $currency;
    $typeLabels = \App\Models\SavingsTransaction::TYPES;
@endphp

<div class="space-y-4">
    {{-- Solde actuel --}}
    <div class="flex items-center justify-between rounded-xl bg-primary-50 px-4 py-3 dark:bg-primary-500/10">
        <div>
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $customer->name }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->phone ?: '—' }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Solde d'épargne</div>
            <div class="text-xl font-black text-primary-600 dark:text-primary-400">{{ $fmt($customer->savings_balance) }}</div>
        </div>
    </div>

    {{-- Derniers mouvements --}}
    @if($movements->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pr-2">Date</th>
                        <th class="py-2 pr-2">Opération</th>
                        <th class="py-2 pr-2 text-right">Montant</th>
                        <th class="py-2 text-right">Solde après</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $t)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-2 pr-2 whitespace-nowrap text-gray-500">@dt($t->created_at, 'd/m/Y H:i')</td>
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
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucun mouvement d'épargne pour ce client.</p>
    @endif
</div>
