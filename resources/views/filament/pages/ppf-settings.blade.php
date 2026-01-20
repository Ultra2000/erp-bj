<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header avec statut --}}
        @php
            $company = \Filament\Facades\Filament::getTenant();
            $isConfigured = $company->emcef_enabled && !empty($company->emcef_token) && !empty($company->emcef_nim);
            $isSandbox = $company->emcef_sandbox ?? true;
        @endphp

        {{-- Instructions pour obtenir le token --}}
        @if(!$isConfigured)
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-3 flex items-center gap-2">
                    <x-heroicon-o-information-circle class="w-5 h-5" />
                    Comment obtenir votre token e-MCeF ?
                </h4>
                <ol class="text-sm text-blue-600 dark:text-blue-400 space-y-2 list-decimal list-inside">
                    <li>Rendez-vous sur <a href="https://developper.impots.bj" target="_blank" class="underline font-medium hover:text-blue-800">https://developper.impots.bj</a></li>
                    <li>Créez un compte développeur avec l'IFU de votre entreprise</li>
                    <li>Une fois connecté, accédez à la section "Token API" ou "Authentification"</li>
                    <li>Générez un nouveau token JWT pour e-MCeF</li>
                    <li>Copiez le token et collez-le ci-dessous</li>
                </ol>
                <p class="mt-3 text-xs text-blue-500 dark:text-blue-400">
                    💡 En mode Sandbox (test), utilisez les identifiants de test fournis par la DGI. 
                    Passez en production uniquement quand tout fonctionne correctement.
                </p>
            </div>
        @endif
        
        <div class="p-4 rounded-lg {{ $isConfigured ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' }}">
            <div class="flex items-center gap-3">
                @if($isConfigured)
                    <x-heroicon-o-shield-check class="w-8 h-8 text-green-600 dark:text-green-400" />
                    <div>
                        <h3 class="font-semibold text-green-700 dark:text-green-300">
                            e-MCeF activé ✅
                            @if($isSandbox)
                                <span class="ml-2 px-2 py-1 text-xs bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-full">
                                    Mode Test
                                </span>
                            @else
                                <span class="ml-2 px-2 py-1 text-xs bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full">
                                    Production
                                </span>
                            @endif
                        </h3>
                        <p class="text-sm text-green-600 dark:text-green-400">
                            NIM : {{ $company->emcef_nim }}
                            | IFU : {{ $company->tax_number }}
                            @if($company->emcef_token_expires_at)
                                | Token valide jusqu'au : {{ $company->emcef_token_expires_at->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    </div>
                @else
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-amber-600 dark:text-amber-400" />
                    <div>
                        <h3 class="font-semibold text-amber-700 dark:text-amber-300">
                            Configuration requise
                        </h3>
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            Obtenez votre token e-MCeF sur le portail développeur de la DGI Bénin
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Statut de connexion en temps réel --}}
        @if($this->connectionStatus && $this->statusInfo)
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-2 flex items-center gap-2">
                    <x-heroicon-o-signal class="w-5 h-5" />
                    Statut de connexion API
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Statut</span>
                        <p class="font-medium {{ ($this->statusInfo['status'] ?? false) ? 'text-green-600' : 'text-red-600' }}">
                            {{ ($this->statusInfo['status'] ?? false) ? '✅ Connecté' : '❌ Déconnecté' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Version API</span>
                        <p class="font-medium">{{ $this->statusInfo['version'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">IFU</span>
                        <p class="font-medium">{{ $this->statusInfo['ifu'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">NIM</span>
                        <p class="font-medium">{{ $this->statusInfo['nim'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Formulaire --}}
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex gap-3">
                <x-filament::button type="submit">
                    Enregistrer la configuration
                </x-filament::button>
                
                <x-filament::button 
                    type="button" 
                    color="gray" 
                    wire:click="testConnection"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="testConnection">
                        <x-heroicon-o-signal class="w-4 h-4 inline mr-1" />
                        Tester la connexion
                    </span>
                    <span wire:loading wire:target="testConnection">
                        Test en cours...
                    </span>
                </x-filament::button>
            </div>
        </form>

        {{-- Historique des factures certifiées --}}
        @if($isConfigured)
            <div class="mt-8">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-document-check class="w-5 h-5" />
                    Dernières factures certifiées
                </h3>
                @php
                    $recentSales = $company->sales()
                        ->whereNotNull('emcef_uid')
                        ->orderByDesc('emcef_certified_at')
                        ->take(10)
                        ->get();
                @endphp
                
                @if($recentSales->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left">Facture</th>
                                    <th class="px-4 py-2 text-left">Client</th>
                                    <th class="px-4 py-2 text-right">Montant</th>
                                    <th class="px-4 py-2 text-center">Statut e-MCeF</th>
                                    <th class="px-4 py-2 text-left">NIM</th>
                                    <th class="px-4 py-2 text-left">Code MECeF</th>
                                    <th class="px-4 py-2 text-left">Certifiée le</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentSales as $sale)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-4 py-2 font-medium">{{ $sale->invoice_number }}</td>
                                        <td class="px-4 py-2">{{ $sale->customer?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-right">{{ number_format($sale->total, 0, ',', ' ') }} FCFA</td>
                                        <td class="px-4 py-2 text-center">
                                            @switch($sale->emcef_status)
                                                @case('certified')
                                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                                        ✅ Certifiée
                                                    </span>
                                                    @break
                                                @case('submitted')
                                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">
                                                        🔄 Soumise
                                                    </span>
                                                    @break
                                                @case('error')
                                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full" title="{{ $sale->emcef_error }}">
                                                        ❌ Erreur
                                                    </span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400 rounded-full">
                                                        🚫 Annulée
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="px-2 py-1 text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">
                                                        ⏳ En attente
                                                    </span>
                                            @endswitch
                                        </td>
                                        <td class="px-4 py-2 font-mono text-xs">{{ $sale->emcef_nim ?? '-' }}</td>
                                        <td class="px-4 py-2 font-mono text-xs">{{ $sale->emcef_code_mecef ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $sale->emcef_certified_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-document class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>Aucune facture certifiée pour le moment</p>
                        <p class="text-sm">Les factures seront automatiquement certifiées lors de leur création</p>
                    </div>
                @endif
            </div>

            {{-- Statistiques --}}
            <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                @php
                    $stats = [
                        'total' => $company->sales()->whereNotNull('emcef_uid')->count(),
                        'certified' => $company->sales()->where('emcef_status', 'certified')->count(),
                        'pending' => $company->sales()->whereIn('emcef_status', ['pending', 'submitted'])->count(),
                        'errors' => $company->sales()->where('emcef_status', 'error')->count(),
                    ];
                @endphp
                
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total soumises</div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['certified'] }}</div>
                    <div class="text-sm text-green-600 dark:text-green-400">Certifiées</div>
                </div>
                
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending'] }}</div>
                    <div class="text-sm text-amber-600 dark:text-amber-400">En attente</div>
                </div>
                
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['errors'] }}</div>
                    <div class="text-sm text-red-600 dark:text-red-400">En erreur</div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
