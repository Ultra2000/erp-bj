<x-filament-panels::page>
    <div class="space-y-6">
        {{-- En-tête avec statistiques --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section class="!p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-primary-100 dark:bg-primary-900/50 rounded-lg">
                        <x-heroicon-o-puzzle-piece class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($features) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Fonctionnalités</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="!p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-success-100 dark:bg-success-900/50 rounded-lg">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ collect($globalFeatures)->filter()->count() }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Actives (global)</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="!p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-warning-100 dark:bg-warning-900/50 rounded-lg">
                        <x-heroicon-o-building-office class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $companies->count() }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Entreprises</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="!p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-info-100 dark:bg-info-900/50 rounded-lg">
                        <x-heroicon-o-squares-2x2 class="w-6 h-6 text-info-600 dark:text-info-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($categories) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Catégories</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Onglets principaux --}}
        <x-filament::tabs>
            <x-filament::tabs.item 
                :active="true" 
                alpine-active="globalTab"
                x-on:click="globalTab = true; companyTab = false"
                icon="heroicon-o-globe-alt"
            >
                Configuration Globale
            </x-filament::tabs.item>
            <x-filament::tabs.item 
                alpine-active="companyTab"
                x-on:click="globalTab = false; companyTab = true"
                icon="heroicon-o-building-office"
            >
                Par Entreprise
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div x-data="{ globalTab: true, companyTab: false }">
            {{-- Configuration Globale --}}
            <div x-show="globalTab" x-transition>
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <span>Configuration Globale des Fonctionnalités</span>
                            <div class="flex gap-2">
                                <x-filament::button 
                                    color="success" 
                                    size="sm"
                                    wire:click="enableAllGlobal"
                                    icon="heroicon-o-check"
                                >
                                    Tout Activer
                                </x-filament::button>
                                <x-filament::button 
                                    color="danger" 
                                    size="sm"
                                    wire:click="disableAllGlobal"
                                    icon="heroicon-o-x-mark"
                                >
                                    Tout Désactiver
                                </x-filament::button>
                            </div>
                        </div>
                    </x-slot>
                    <x-slot name="description">
                        Ces paramètres définissent les fonctionnalités par défaut disponibles pour toutes les nouvelles entreprises.
                    </x-slot>

                    <div class="space-y-6">
                        @foreach($categories as $categoryKey => $category)
                            <div class="border dark:border-gray-700 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="p-1.5 bg-{{ $category['color'] }}-100 dark:bg-{{ $category['color'] }}-900/50 rounded-lg">
                                        @svg($category['icon'], 'w-5 h-5 text-' . $category['color'] . '-600 dark:text-' . $category['color'] . '-400')
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $category['label'] }}</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($features as $featureKey => $feature)
                                        @if($feature['category'] === $categoryKey)
                                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                                <div class="flex-shrink-0 mt-0.5">
                                                    <x-filament::input.checkbox
                                                        wire:model.live="globalFeatures.{{ $featureKey }}"
                                                    />
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        @svg($feature['icon'], 'w-4 h-4 text-gray-500')
                                                        <span class="font-medium text-gray-900 dark:text-white">{{ $feature['label'] }}</span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $feature['description'] }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-between items-center mt-6 pt-4 border-t dark:border-gray-700">
                        <x-filament::button 
                            color="warning"
                            wire:click="applyGlobalToAllCompanies"
                            wire:confirm="Êtes-vous sûr de vouloir appliquer cette configuration à TOUTES les entreprises ?"
                            icon="heroicon-o-arrow-path"
                        >
                            Appliquer à toutes les entreprises
                        </x-filament::button>

                        <x-filament::button 
                            wire:click="saveGlobalFeatures"
                            icon="heroicon-o-check"
                        >
                            Sauvegarder la Configuration Globale
                        </x-filament::button>
                    </div>
                </x-filament::section>
            </div>

            {{-- Configuration par Entreprise --}}
            <div x-show="companyTab" x-transition x-cloak>
                <x-filament::section>
                    <x-slot name="heading">
                        Configuration par Entreprise
                    </x-slot>
                    <x-slot name="description">
                        Personnalisez les fonctionnalités disponibles pour chaque entreprise individuellement.
                    </x-slot>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sélectionner une entreprise
                        </label>
                        <select 
                            wire:model.live="selectedCompanyId"
                            class="w-full md:w-1/2 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">-- Choisir une entreprise --</option>
                            @foreach($companies as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedCompanyId)
                        <div class="flex gap-2 mb-6">
                            <x-filament::button 
                                color="success" 
                                size="sm"
                                wire:click="enableAllCompany"
                                icon="heroicon-o-check"
                            >
                                Tout Activer
                            </x-filament::button>
                            <x-filament::button 
                                color="danger" 
                                size="sm"
                                wire:click="disableAllCompany"
                                icon="heroicon-o-x-mark"
                            >
                                Tout Désactiver
                            </x-filament::button>
                            <x-filament::button 
                                color="gray" 
                                size="sm"
                                wire:click="applyGlobalToCompany"
                                icon="heroicon-o-arrow-down-tray"
                            >
                                Appliquer config globale
                            </x-filament::button>
                        </div>

                        <div class="space-y-6">
                            @foreach($categories as $categoryKey => $category)
                                <div class="border dark:border-gray-700 rounded-xl p-4">
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="p-1.5 bg-{{ $category['color'] }}-100 dark:bg-{{ $category['color'] }}-900/50 rounded-lg">
                                            @svg($category['icon'], 'w-5 h-5 text-' . $category['color'] . '-600 dark:text-' . $category['color'] . '-400')
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $category['label'] }}</h3>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($features as $featureKey => $feature)
                                            @if($feature['category'] === $categoryKey)
                                                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                                    <div class="flex-shrink-0 mt-0.5">
                                                        <x-filament::input.checkbox
                                                            wire:model.live="companyFeatures.{{ $featureKey }}"
                                                        />
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-2">
                                                            @svg($feature['icon'], 'w-4 h-4 text-gray-500')
                                                            <span class="font-medium text-gray-900 dark:text-white">{{ $feature['label'] }}</span>
                                                        </div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $feature['description'] }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-end mt-6 pt-4 border-t dark:border-gray-700">
                            <x-filament::button 
                                wire:click="saveCompanyFeatures"
                                icon="heroicon-o-check"
                            >
                                Sauvegarder pour cette entreprise
                            </x-filament::button>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <x-heroicon-o-building-office class="w-12 h-12 mx-auto text-gray-400" />
                            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Aucune entreprise sélectionnée</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Sélectionnez une entreprise dans la liste pour configurer ses fonctionnalités.
                            </p>
                        </div>
                    @endif
                </x-filament::section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
