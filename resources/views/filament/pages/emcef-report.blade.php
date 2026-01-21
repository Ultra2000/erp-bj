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

        {{-- TVA COLLECTÉE (Ventes) --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-success-600">
                    <x-heroicon-o-arrow-trending-up class="w-5 h-5" />
                    TVA Collectée (Ventes)
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Factures --}}
                <div class="bg-success-50 dark:bg-success-900/20 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-success-600 mb-2">
                        <x-heroicon-o-document-check class="w-4 h-4" />
                        <span class="text-sm font-medium">Factures certifiées</span>
                    </div>
                    <div class="text-3xl font-bold text-success-600">{{ $stats['total_invoices'] }}</div>
                    <div class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total HT</span>
                            <span class="font-semibold">{{ number_format($stats['total_ht'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">TVA</span>
                            <span class="font-semibold text-success-600">+ {{ number_format($stats['total_vat'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between font-medium pt-1 border-t border-success-200 dark:border-success-800">
                            <span>Total TTC</span>
                            <span>{{ number_format($stats['total_ttc'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                    </div>
                </div>

                {{-- Avoirs --}}
                <div class="bg-danger-50 dark:bg-danger-900/20 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-danger-600 mb-2">
                        <x-heroicon-o-document-minus class="w-4 h-4" />
                        <span class="text-sm font-medium">Avoirs émis</span>
                    </div>
                    <div class="text-3xl font-bold text-danger-600">{{ $stats['total_credit_notes'] }}</div>
                    <div class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total HT</span>
                            <span class="font-semibold text-danger-600">- {{ number_format($stats['credit_notes_ht'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">TVA</span>
                            <span class="font-semibold text-danger-600">- {{ number_format($stats['credit_notes_vat'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between font-medium pt-1 border-t border-danger-200 dark:border-danger-800">
                            <span>Total TTC</span>
                            <span class="text-danger-600">- {{ number_format($stats['credit_notes_ttc'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                    </div>
                </div>

                {{-- Net Collecté --}}
                <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-primary-600 mb-2">
                        <x-heroicon-o-calculator class="w-4 h-4" />
                        <span class="text-sm font-medium">TVA Collectée Nette</span>
                    </div>
                    <div class="text-3xl font-bold text-primary-600">{{ number_format($stats['net_vat'], 0, ',', ' ') }} {{ $currency }}</div>
                    <div class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">CA HT net</span>
                            <span class="font-semibold">{{ number_format($stats['net_ht'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between font-medium pt-1 border-t border-primary-200 dark:border-primary-800">
                            <span>CA TTC net</span>
                            <span>{{ number_format($stats['net_ttc'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- TVA DÉDUCTIBLE (Achats) --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-orange-600">
                    <x-heroicon-o-arrow-trending-down class="w-5 h-5" />
                    TVA Déductible (Achats)
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Achats --}}
                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-orange-600 mb-2">
                        <x-heroicon-o-shopping-cart class="w-4 h-4" />
                        <span class="text-sm font-medium">Achats du mois</span>
                    </div>
                    <div class="text-3xl font-bold text-orange-600">{{ $stats['total_purchases'] }}</div>
                    <div class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total HT</span>
                            <span class="font-semibold">{{ number_format($stats['purchases_ht'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">TVA déductible</span>
                            <span class="font-semibold text-orange-600">- {{ number_format($stats['purchases_vat'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between font-medium pt-1 border-t border-orange-200 dark:border-orange-800">
                            <span>Total TTC</span>
                            <span>{{ number_format($stats['purchases_ttc'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                    </div>
                </div>

                {{-- Récapitulatif TVA Due --}}
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg p-4 text-white">
                    <div class="flex items-center gap-2 mb-4">
                        <x-heroicon-o-banknotes class="w-5 h-5" />
                        <span class="font-medium">TVA Nette Due (DGI)</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="opacity-80">TVA Collectée</span>
                            <span class="font-semibold">+ {{ number_format($stats['net_vat'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-80">TVA Déductible</span>
                            <span class="font-semibold">- {{ number_format($stats['purchases_vat'], 0, ',', ' ') }} {{ $currency }}</span>
                        </div>
                        <div class="flex justify-between pt-2 mt-2 border-t border-white/30 text-lg">
                            <span class="font-medium">TVA à reverser</span>
                            <span class="font-bold {{ $stats['vat_due'] >= 0 ? '' : 'text-green-300' }}">
                                {{ $stats['vat_due'] >= 0 ? '' : 'Crédit: ' }}{{ number_format(abs($stats['vat_due']), 0, ',', ' ') }} {{ $currency }}
                            </span>
                        </div>
                    </div>
                    @if($stats['vat_due'] < 0)
                        <div class="mt-3 text-xs bg-white/20 rounded px-2 py-1">
                            💡 Crédit de TVA reportable sur le mois suivant
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- Ventilation TVA Déductible --}}
        @if(!empty($stats['vat_deductible_breakdown']))
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-5 h-5 text-orange-500" />
                    Ventilation TVA déductible par taux
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-4">Taux TVA</th>
                            <th class="text-right py-2 px-4">Nb Achats</th>
                            <th class="text-right py-2 px-4">Base HT</th>
                            <th class="text-right py-2 px-4">TVA Déductible</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['vat_deductible_breakdown'] as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                        {{ number_format($row['vat_rate'] ?? 18, 0) }}%
                                    </span>
                                </td>
                                <td class="py-2 px-4 text-right">{{ $row['invoice_count'] }}</td>
                                <td class="py-2 px-4 text-right font-medium">{{ number_format($row['base_ht'], 0, ',', ' ') }} {{ $currency }}</td>
                                <td class="py-2 px-4 text-right font-semibold text-orange-600">{{ number_format($row['vat_amount'], 0, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        @endif

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
