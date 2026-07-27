<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-trophy class="h-5 w-5 text-amber-500" />
                Top produits du mois
            </div>
        </x-slot>

        @php
            $products = $this->getTopProducts();
            $currency = $this->getCurrencyLabel();
            $max = collect($products)->max('revenue') ?: 1;
        @endphp

        @if(count($products) > 0)
            <div class="space-y-3">
                @foreach($products as $i => $p)
                    <div>
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold
                                    {{ $i === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' }}">
                                    {{ $i + 1 }}
                                </span>
                                <span class="truncate font-medium text-gray-800 dark:text-gray-200">{{ $p['name'] }}</span>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($p['revenue'], 0, ',', ' ') }} {{ $currency }}</span>
                                <span class="ml-1 text-xs text-gray-400">({{ number_format($p['qty'], 0, ',', ' ') }} u.)</span>
                            </div>
                        </div>
                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ max(3, round($p['revenue'] / $max * 100)) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <x-heroicon-o-cube class="mx-auto mb-2 h-10 w-10 text-gray-300 dark:text-gray-600" />
                Aucune vente ce mois-ci pour le moment.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
