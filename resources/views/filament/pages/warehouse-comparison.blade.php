<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Sélecteur de période --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-calendar class="w-5 h-5 text-primary-500" />
                    Période d'analyse
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
            $stats = $this->getWarehouseStats();
            $evolution = $this->getMonthlyEvolution();
            $currency = 'FCFA';
        @endphp

        {{-- Classement des boutiques --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-trophy class="w-5 h-5 text-warning-500" />
                    Classement des Boutiques - {{ $stats['period'] }}
                </div>
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($stats['warehouses'] as $warehouse)
                    <div class="relative bg-gradient-to-br {{ $warehouse['rank'] === 1 ? 'from-yellow-50 to-yellow-100 dark:from-yellow-900/30 dark:to-yellow-800/20 border-yellow-300' : ($warehouse['rank'] === 2 ? 'from-gray-50 to-gray-100 dark:from-gray-700/30 dark:to-gray-600/20 border-gray-300' : ($warehouse['rank'] === 3 ? 'from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/20 border-orange-300' : 'from-white to-gray-50 dark:from-gray-800 dark:to-gray-700 border-gray-200')) }} rounded-xl p-4 border-2 shadow-sm hover:shadow-md transition-shadow">
                        {{-- Badge rang --}}
                        <div class="absolute -top-2 -left-2">
                            @if($warehouse['rank'] === 1)
                                <span class="text-2xl">🥇</span>
                            @elseif($warehouse['rank'] === 2)
                                <span class="text-2xl">🥈</span>
                            @elseif($warehouse['rank'] === 3)
                                <span class="text-2xl">🥉</span>
                            @else
                                <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold bg-gray-500 text-white rounded-full">{{ $warehouse['rank'] }}</span>
                            @endif
                        </div>

                        {{-- Nom boutique --}}
                        <div class="text-center mt-2">
                            <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ $warehouse['name'] }}</h3>
                        </div>

                        {{-- CA --}}
                        <div class="text-center mt-3">
                            <div class="text-2xl font-bold {{ $warehouse['rank'] === 1 ? 'text-yellow-600' : 'text-primary-600' }}">
                                {{ number_format($warehouse['current_ca'] / 1000, 0, ',', ' ') }}K
                            </div>
                            <div class="text-xs text-gray-500">{{ $currency }}</div>
                        </div>

                        {{-- Évolution --}}
                        <div class="text-center mt-2">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $warehouse['evolution'] >= 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                @if($warehouse['evolution'] >= 0)
                                    <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                                @else
                                    <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                                @endif
                                {{ $warehouse['evolution'] >= 0 ? '+' : '' }}{{ $warehouse['evolution'] }}%
                            </span>
                        </div>

                        {{-- Détails --}}
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                            <div class="flex justify-between">
                                <span>Ventes</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $warehouse['current_count'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Part</span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $warehouse['percentage'] }}%</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Graphiques --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:key="charts-{{ $selectedMonth }}-{{ $selectedYear }}">
            {{-- Graphique en barres - CA par boutique --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-500" />
                        Chiffre d'affaires par Boutique
                    </div>
                </x-slot>

                <div class="h-80">
                    <canvas id="barChart-{{ $selectedMonth }}-{{ $selectedYear }}"></canvas>
                </div>
            </x-filament::section>

            {{-- Graphique camembert - Répartition --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-pie class="w-5 h-5 text-primary-500" />
                        Répartition du CA
                    </div>
                </x-slot>

                <div class="h-80 flex items-center justify-center">
                    <canvas id="pieChart-{{ $selectedMonth }}-{{ $selectedYear }}"></canvas>
                </div>
            </x-filament::section>
        </div>

        {{-- Graphique évolution --}}
        <x-filament::section wire:key="evolution-chart-{{ $selectedMonth }}-{{ $selectedYear }}">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar-square class="w-5 h-5 text-primary-500" />
                    Évolution sur 6 mois
                </div>
            </x-slot>

            <div class="h-96">
                <canvas id="lineChart-{{ $selectedMonth }}-{{ $selectedYear }}"></canvas>
            </div>
        </x-filament::section>

        {{-- Comparaison mois précédent --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-scale class="w-5 h-5 text-primary-500" />
                    Comparaison {{ $stats['period'] }} vs {{ $stats['previous_period'] }}
                </div>
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                    $totalCurrentCA = collect($stats['warehouses'])->sum('current_ca');
                    $totalPreviousCA = collect($stats['warehouses'])->sum('previous_ca');
                    $totalCurrentCount = collect($stats['warehouses'])->sum('current_count');
                    $totalPreviousCount = collect($stats['warehouses'])->sum('previous_count');
                    $globalEvolution = $totalPreviousCA > 0 ? round((($totalCurrentCA - $totalPreviousCA) / $totalPreviousCA) * 100, 1) : 0;
                @endphp

                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                    <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">CA Total Actuel</div>
                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">
                        {{ number_format($totalCurrentCA, 0, ',', ' ') }}
                    </div>
                    <div class="text-xs text-blue-500">{{ $currency }}</div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">CA Mois Précédent</div>
                    <div class="text-2xl font-bold text-gray-700 dark:text-gray-300 mt-1">
                        {{ number_format($totalPreviousCA, 0, ',', ' ') }}
                    </div>
                    <div class="text-xs text-gray-500">{{ $currency }}</div>
                </div>

                <div class="bg-{{ $globalEvolution >= 0 ? 'green' : 'red' }}-50 dark:bg-{{ $globalEvolution >= 0 ? 'green' : 'red' }}-900/20 rounded-lg p-4 text-center">
                    <div class="text-sm text-{{ $globalEvolution >= 0 ? 'green' : 'red' }}-600 dark:text-{{ $globalEvolution >= 0 ? 'green' : 'red' }}-400 font-medium">Évolution Globale</div>
                    <div class="text-2xl font-bold text-{{ $globalEvolution >= 0 ? 'green' : 'red' }}-700 dark:text-{{ $globalEvolution >= 0 ? 'green' : 'red' }}-300 mt-1">
                        {{ $globalEvolution >= 0 ? '+' : '' }}{{ $globalEvolution }}%
                    </div>
                    <div class="text-xs text-{{ $globalEvolution >= 0 ? 'green' : 'red' }}-500">
                        {{ $globalEvolution >= 0 ? '↑' : '↓' }} {{ number_format(abs($totalCurrentCA - $totalPreviousCA), 0, ',', ' ') }} {{ $currency }}
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                    <div class="text-sm text-purple-600 dark:text-purple-400 font-medium">Nombre de Ventes</div>
                    <div class="text-2xl font-bold text-purple-700 dark:text-purple-300 mt-1">
                        {{ $totalCurrentCount }}
                    </div>
                    <div class="text-xs text-purple-500">vs {{ $totalPreviousCount }} le mois dernier</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Tableau détaillé --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-5 h-5 text-primary-500" />
                    Détail par Boutique
                </div>
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>

    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        function initCharts() {
            // Données PHP vers JS
            const warehouseData = @json($stats['warehouses']);
            const evolutionData = @json($evolution);
            const chartKey = '{{ $selectedMonth }}-{{ $selectedYear }}';
            
            // Couleurs
            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];
            
            // Graphique en barres
            const barCanvas = document.getElementById('barChart-' + chartKey);
            if (barCanvas) {
                new Chart(barCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: warehouseData.map(w => w.name),
                        datasets: [{
                            label: 'CA TTC (FCFA)',
                            data: warehouseData.map(w => w.current_ca),
                            backgroundColor: warehouseData.map((_, i) => colors[i % colors.length] + '80'),
                            borderColor: warehouseData.map((_, i) => colors[i % colors.length]),
                            borderWidth: 2,
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return new Intl.NumberFormat('fr-FR').format(context.raw) + ' FCFA';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(value);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Graphique camembert
            const pieCanvas = document.getElementById('pieChart-' + chartKey);
            if (pieCanvas) {
                new Chart(pieCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: warehouseData.map(w => w.name),
                        datasets: [{
                            data: warehouseData.map(w => w.current_ca),
                            backgroundColor: warehouseData.map((_, i) => colors[i % colors.length]),
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.raw / total) * 100).toFixed(1);
                                        return context.label + ': ' + percentage + '% (' + new Intl.NumberFormat('fr-FR').format(context.raw) + ' FCFA)';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Graphique ligne évolution
            const lineCanvas = document.getElementById('lineChart-' + chartKey);
            if (lineCanvas) {
                new Chart(lineCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: evolutionData.labels,
                        datasets: evolutionData.datasets.map((dataset, i) => ({
                            label: dataset.name,
                            data: dataset.data,
                            borderColor: dataset.color,
                            backgroundColor: dataset.color + '20',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + new Intl.NumberFormat('fr-FR').format(context.raw) + ' FCFA';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(value);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // Initialisation au chargement
        document.addEventListener('DOMContentLoaded', initCharts);
        
        // Réinitialisation après mise à jour Livewire
        document.addEventListener('livewire:load', initCharts);
        
        // Pour Livewire 3
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', ({ el }) => {
                setTimeout(initCharts, 100);
            });
        }
    </script>
</x-filament-panels::page>
