<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Sélecteur de période --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-calendar class="w-5 h-5 text-primary-500" />
                    Période de déclaration
                </div>
            </x-slot>

            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mois</label>
                    <select wire:model.live="selectedMonth" class="block w-40 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach(range(1, 12) as $month)
                            <option value="{{ $month }}">
                                {{ ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'][$month - 1] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Année</label>
                    <select wire:model.live="selectedYear" class="block w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach(range(now()->year, now()->year - 5) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>

        @php
            $stats = $this->getMonthlyStats();
            $currency = 'FCFA';
        @endphp

        {{-- Résumé principal --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Factures --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-success-600">
                        <x-heroicon-o-document-check class="w-5 h-5" />
                        Factures certifiées
                    </div>
                </x-slot>

                <div class="text-center">
                    <div class="text-4xl font-bold text-success-600">{{ $stats['total_invoices'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">factures</div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Total HT</span>
                        <span class="font-semibold">{{ number_format($stats['total_ht'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">TVA collectée</span>
                        <span class="font-semibold text-primary-600">{{ number_format($stats['total_vat'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                    <div class="flex justify-between text-lg">
                        <span class="font-medium">Total TTC</span>
                        <span class="font-bold text-success-600">{{ number_format($stats['total_ttc'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                </div>
            </x-filament::section>

            {{-- Avoirs --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-danger-600">
                        <x-heroicon-o-document-minus class="w-5 h-5" />
                        Avoirs émis
                    </div>
                </x-slot>

                <div class="text-center">
                    <div class="text-4xl font-bold text-danger-600">{{ $stats['total_credit_notes'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">avoirs</div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Total HT</span>
                        <span class="font-semibold text-danger-600">- {{ number_format($stats['credit_notes_ht'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">TVA déduite</span>
                        <span class="font-semibold text-danger-600">- {{ number_format($stats['credit_notes_vat'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                    <div class="flex justify-between text-lg">
                        <span class="font-medium">Total TTC</span>
                        <span class="font-bold text-danger-600">- {{ number_format($stats['credit_notes_ttc'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                </div>
            </x-filament::section>

            {{-- Net à déclarer --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-warning-600">
                        <x-heroicon-o-calculator class="w-5 h-5" />
                        Net à déclarer (DGI)
                    </div>
                </x-slot>

                <div class="text-center">
                    <div class="text-4xl font-bold text-warning-600">{{ $stats['total_invoices'] - $stats['total_credit_notes'] }}</div>
                    <div class="text-sm text-gray-500 mt-1">opérations nettes</div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Base HT nette</span>
                        <span class="font-semibold">{{ number_format($stats['net_ht'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                    <div class="flex justify-between bg-primary-50 dark:bg-primary-900/20 -mx-4 px-4 py-2 rounded">
                        <span class="font-medium text-primary-700 dark:text-primary-300">TVA nette à reverser</span>
                        <span class="font-bold text-primary-700 dark:text-primary-300">{{ number_format($stats['net_vat'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                    <div class="flex justify-between text-lg">
                        <span class="font-medium">Chiffre d'affaires TTC</span>
                        <span class="font-bold">{{ number_format($stats['net_ttc'], 0, ',', ' ') }} {{ $currency }}</span>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Compteurs e-MCeF --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-hashtag class="w-5 h-5 text-primary-500" />
                    Compteurs e-MCeF du mois
                </div>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Premier NIM</div>
                    <div class="font-mono text-lg font-semibold mt-1">{{ $stats['first_nim'] ?? '-' }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Dernier NIM</div>
                    <div class="font-mono text-lg font-semibold mt-1">{{ $stats['last_nim'] ?? '-' }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Premier Code MECeF</div>
                    <div class="font-mono text-sm mt-1 break-all">{{ $stats['first_code_mecef'] ?? '-' }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wide">Dernier Code MECeF</div>
                    <div class="font-mono text-sm mt-1 break-all">{{ $stats['last_code_mecef'] ?? '-' }}</div>
                </div>
            </div>

            @if($stats['counters'])
                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">Compteurs DGI (dernière facture)</div>
                    <div class="font-mono text-sm text-blue-600 dark:text-blue-400">{{ $stats['counters'] }}</div>
                </div>
            @endif
        </x-filament::section>

        {{-- Ventilation TVA --}}
        @if(!empty($stats['vat_breakdown']))
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-pie class="w-5 h-5 text-primary-500" />
                    Ventilation par groupe de taxe
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-4">Groupe</th>
                            <th class="text-left py-2 px-4">Taux TVA</th>
                            <th class="text-right py-2 px-4">Nb Factures</th>
                            <th class="text-right py-2 px-4">Base HT</th>
                            <th class="text-right py-2 px-4">TVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['vat_breakdown'] as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                        {{ $row['vat_category'] ?? 'A' }}
                                    </span>
                                </td>
                                <td class="py-2 px-4">{{ number_format($row['vat_rate'], 0) }}%</td>
                                <td class="py-2 px-4 text-right">{{ $row['invoice_count'] }}</td>
                                <td class="py-2 px-4 text-right font-medium">{{ number_format($row['base_ht'], 0, ',', ' ') }} {{ $currency }}</td>
                                <td class="py-2 px-4 text-right font-semibold text-primary-600">{{ number_format($row['vat_amount'], 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

        {{-- Ventilation par mode de paiement --}}
        @if(!empty($stats['payment_breakdown']))
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-banknotes class="w-5 h-5 text-primary-500" />
                    Ventilation par mode de paiement
                </div>
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                    $paymentLabels = [
                        'cash' => ['label' => 'Espèces', 'icon' => '💵', 'color' => 'green'],
                        'card' => ['label' => 'Carte bancaire', 'icon' => '💳', 'color' => 'blue'],
                        'transfer' => ['label' => 'Virement', 'icon' => '🏦', 'color' => 'purple'],
                        'mobile_money' => ['label' => 'Mobile Money', 'icon' => '📱', 'color' => 'yellow'],
                        'check' => ['label' => 'Chèque', 'icon' => '📝', 'color' => 'gray'],
                        'credit' => ['label' => 'Crédit', 'icon' => '📋', 'color' => 'orange'],
                        'other' => ['label' => 'Autre', 'icon' => '💰', 'color' => 'gray'],
                    ];
                @endphp
                @foreach($stats['payment_breakdown'] as $payment)
                    @php
                        $info = $paymentLabels[$payment['payment_method']] ?? ['label' => ucfirst($payment['payment_method'] ?? 'Autre'), 'icon' => '💰', 'color' => 'gray'];
                    @endphp
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ $info['icon'] }}</span>
                            {{ $info['label'] }}
                        </div>
                        <div class="text-xl font-bold mt-1">{{ number_format($payment['total'], 0, ',', ' ') }} {{ $currency }}</div>
                        <div class="text-xs text-gray-500">{{ $payment['count'] }} transaction(s)</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
        @endif

        {{-- Liste des factures --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-text class="w-5 h-5 text-primary-500" />
                    Détail des factures certifiées
                </div>
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
