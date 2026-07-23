<x-filament-panels::page>
    @php
        $summary = $this->getDebtSummary();
        $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    @endphp

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        {{-- Total des créances --}}
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total des créances</div>
            <div class="mt-1 text-3xl font-bold text-danger-600 dark:text-danger-400">{{ $fmt($summary['total']) }} <span class="text-lg font-semibold">FCFA</span></div>
            <div class="mt-1 text-xs text-gray-400">Montant total dû par les clients</div>
        </div>

        {{-- Clients débiteurs --}}
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Clients débiteurs</div>
            <div class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $summary['customers'] }}</div>
            <div class="mt-1 text-xs text-gray-400">{{ $summary['invoices'] }} facture(s) impayée(s)</div>
        </div>

        {{-- Créance la plus ancienne --}}
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Créance la plus ancienne</div>
            <div class="mt-1 text-3xl font-bold {{ $summary['oldest_days'] > 30 ? 'text-danger-600 dark:text-danger-400' : ($summary['oldest_days'] > 15 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-900 dark:text-white') }}">
                {{ $summary['oldest_days'] }} <span class="text-lg font-semibold">jour(s)</span>
            </div>
            <div class="mt-1 text-xs text-gray-400">Relancez en priorité les plus anciennes</div>
        </div>

        {{-- Aide relance --}}
        <div class="fi-wi-stats-overview-stat relative rounded-xl bg-primary-50 p-6 shadow-sm ring-1 ring-primary-600/10 dark:bg-primary-500/10 dark:ring-primary-400/20">
            <div class="text-sm font-medium text-primary-700 dark:text-primary-300">Relances</div>
            <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Triez par <strong>Ancienneté</strong> ou <strong>Montant dû</strong>, copiez le téléphone d'un clic et cliquez sur <strong>Détail</strong> pour voir chaque facture impayée.
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
