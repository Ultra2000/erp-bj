@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' ' . $currency;
    $totalQty = $articles->sum('qty');
    $totalMontant = $articles->sum('montant');
    $fmtQty = fn ($q) => $q == (int) $q ? (string) (int) $q : number_format((float) $q, 2, ',', ' ');
@endphp

<div class="space-y-4">
    <div class="rounded-xl bg-primary-50 px-4 py-3 text-sm dark:bg-primary-500/10">
        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $customer->name }}</span>
        <span class="text-gray-500 dark:text-gray-400">
            — du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}
        </span>
    </div>

    @if($articles->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <th class="py-2 pr-2">#</th>
                        <th class="py-2 pr-2">Article</th>
                        <th class="py-2 pr-2 text-right">Quantité</th>
                        <th class="py-2 text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($articles as $i => $a)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-2 pr-2 font-bold text-gray-400">{{ $i + 1 }}</td>
                            <td class="py-2 pr-2 font-medium text-gray-800 dark:text-gray-100">{{ $a->product_name ?? 'Produit supprimé' }}</td>
                            <td class="py-2 pr-2 text-right font-bold text-primary-600 dark:text-primary-400">{{ $fmtQty($a->qty) }}</td>
                            <td class="py-2 text-right text-gray-600 dark:text-gray-300">{{ $fmt($a->montant) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 dark:border-white/10 font-bold">
                        <td class="py-2 pr-2" colspan="2">TOTAL</td>
                        <td class="py-2 pr-2 text-right">{{ $fmtQty($totalQty) }}</td>
                        <td class="py-2 text-right">{{ $fmt($totalMontant) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">Aucun article acheté sur cette période.</p>
    @endif
</div>
