<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Instructions --}}
        <x-filament::section>
            <x-slot name="heading">
                Instructions d'import
            </x-slot>
            
            <div class="prose dark:prose-invert max-w-none">
                <p>Cette fonctionnalité vous permet d'importer vos produits depuis un fichier Excel pour faciliter la migration.</p>
                
                <h4>Colonnes du fichier</h4>
                <ul class="text-sm">
                    <li><strong>nom</strong> (obligatoire) : Nom du produit</li>
                    <li><strong>prix_vente</strong> (obligatoire) : Prix de vente en FCFA</li>
                    <li><strong>code_barre</strong> : Code-barres du produit</li>
                    <li><strong>description</strong> : Description du produit</li>
                    <li><strong>prix_achat</strong> : Prix d'achat en FCFA</li>
                    <li><strong>stock</strong> : Stock initial</li>
                    <li><strong>stock_min</strong> : Seuil d'alerte stock</li>
                    <li><strong>unite</strong> : Unité (pièce, kg, litre...)</li>
                    <li><strong>tva_achat</strong> : Taux TVA achat (ex: 18)</li>
                    <li><strong>tva_vente</strong> : Taux TVA vente (ex: 18)</li>
                    <li><strong>prix_gros</strong> : Prix de gros (optionnel)</li>
                    <li><strong>qte_min_gros</strong> : Quantité minimum pour prix de gros</li>
                    <li><strong>fournisseur</strong> : Nom ou code du fournisseur</li>
                    <li><strong>prix_ttc</strong> : "Oui" si prix en TTC, "Non" si prix en HT</li>
                </ul>

                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <p class="text-amber-800 dark:text-amber-200 font-medium flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                        Important
                    </p>
                    <ul class="text-sm text-amber-700 dark:text-amber-300 mt-2">
                        <li>Si un produit avec le même <strong>code-barres</strong> ou <strong>nom</strong> existe déjà, il sera <strong>mis à jour</strong> (sauf le stock).</li>
                        <li>Les nouveaux produits seront assignés automatiquement à l'entrepôt par défaut.</li>
                        <li>Téléchargez le modèle pour voir le format attendu.</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>

        {{-- Formulaire d'upload --}}
        <x-filament::section>
            <x-slot name="heading">
                Importer un fichier
            </x-slot>

            <form wire:submit="import">
                {{ $this->form }}

                <div class="mt-4">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <x-filament::loading-indicator class="h-4 w-4 mr-2" wire:loading wire:target="import" />
                        <span wire:loading.remove wire:target="import">
                            <x-heroicon-o-arrow-up-tray class="w-4 h-4 mr-2 inline" />
                        </span>
                        Lancer l'import
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Résultats de l'import --}}
        @if($importCompleted)
            <x-filament::section>
                <x-slot name="heading">
                    Résultat de l'import
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $importedCount }}</div>
                        <div class="text-sm text-green-700 dark:text-green-300">Produits créés</div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $updatedCount }}</div>
                        <div class="text-sm text-blue-700 dark:text-blue-300">Produits mis à jour</div>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $skippedCount }}</div>
                        <div class="text-sm text-amber-700 dark:text-amber-300">Lignes ignorées</div>
                    </div>
                </div>

                @if(count($errors) > 0)
                    <div class="mt-4">
                        <h4 class="font-semibold text-red-600 dark:text-red-400 mb-2">Erreurs rencontrées :</h4>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 max-h-60 overflow-y-auto">
                            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                                @foreach($errors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <x-filament::link href="{{ \App\Filament\Resources\ProductResource::getUrl('index') }}">
                        <x-heroicon-o-arrow-left class="w-4 h-4 mr-1 inline" />
                        Retour à la liste des produits
                    </x-filament::link>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
