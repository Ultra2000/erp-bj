@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $totalRemaining = $sales->sum('remaining');
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Client</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $customer->name }}</p>
        </div>
        @if($customer->phone)
        <div class="text-right">
            <p class="text-sm text-gray-500 dark:text-gray-400">Téléphone</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ $customer->phone }}</p>
        </div>
        @endif
        <div class="text-right">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total dû</p>
            <p class="text-lg font-bold text-danger-600 dark:text-danger-400">{{ $fmt($totalRemaining) }} FCFA</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    <th class="py-2 pr-3">N° Facture</th>
                    <th class="py-2 px-3">Date</th>
                    <th class="py-2 px-3 text-center">Ancienneté</th>
                    <th class="py-2 px-3 text-right">Total</th>
                    <th class="py-2 px-3 text-right">Payé</th>
                    <th class="py-2 pl-3 text-right">Reste dû</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($sales as $s)
                <tr>
                    <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white">{{ $s['invoice_number'] }}</td>
                    <td class="py-2 px-3 text-gray-600 dark:text-gray-300">{{ $s['date'] }}</td>
                    <td class="py-2 px-3 text-center">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $s['days'] > 30 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : ($s['days'] > 15 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300') }}">
                            {{ $s['days'] }} j
                        </span>
                    </td>
                    <td class="py-2 px-3 text-right text-gray-600 dark:text-gray-300">{{ $fmt($s['total']) }}</td>
                    <td class="py-2 px-3 text-right text-green-600 dark:text-green-400">{{ $fmt($s['paid']) }}</td>
                    <td class="py-2 pl-3 text-right font-bold text-danger-600 dark:text-danger-400">{{ $fmt($s['remaining']) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-300 font-bold dark:border-gray-600">
                    <td class="py-2 pr-3" colspan="5">Total reste dû</td>
                    <td class="py-2 pl-3 text-right text-danger-600 dark:text-danger-400">{{ $fmt($totalRemaining) }} FCFA</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
