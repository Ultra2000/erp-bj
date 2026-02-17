<x-filament-panels::page>
    <div x-data="{ activeSection: @entangle('activeSection') }" class="guide-container">

        {{-- Navigation latérale + contenu --}}
        <div class="flex flex-col lg:flex-row gap-6">

            {{-- Sidebar navigation --}}
            <div class="lg:w-64 shrink-0">
                <nav class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-3 sticky top-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-3 mb-3">Sections du guide</h3>
                    @foreach($this->getSections() as $key => $section)
                        <button
                            wire:click="setSection('{{ $key }}')"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                                {{ $activeSection === $key
                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200' }}"
                        >
                            <x-dynamic-component :component="$section['icon']" class="h-5 w-5 shrink-0" />
                            <span>{{ $section['label'] }}</span>
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Contenu principal --}}
            <div class="flex-1 min-w-0">

                {{-- ============================================================ --}}
                {{-- VUE D'ENSEMBLE --}}
                {{-- ============================================================ --}}
                @if($activeSection === 'overview')
                <div class="space-y-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-500/10">
                                <x-heroicon-o-academic-cap class="h-7 w-7 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Bienvenue sur FRECORP ERP</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Guide complet d'utilisation de votre logiciel de gestion</p>
                            </div>
                        </div>

                        <div class="prose dark:prose-invert max-w-none text-sm">
                            <p>FRECORP ERP est une solution complète de gestion d'entreprise adaptée aux normes béninoises (DGI, e-MCeF, AIB).
                            Ce guide vous accompagne dans l'utilisation de chaque module.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                            @foreach([
                                ['sales', 'heroicon-o-shopping-cart', 'Ventes', 'Factures, clients, devis, bons de livraison', 'primary'],
                                ['pos', 'heroicon-o-calculator', 'Point de Vente', 'Caisse, sessions, encaissements', 'success'],
                                ['stock', 'heroicon-o-cube', 'Stocks & Achats', 'Produits, fournisseurs, inventaires', 'warning'],
                                ['accounting', 'heroicon-o-banknotes', 'Comptabilité', 'Écritures, journaux, bilans', 'danger'],
                                ['hr', 'heroicon-o-user-group', 'RH', 'Employés, pointages, congés', 'info'],
                                ['invoicing', 'heroicon-o-document-text', 'Facturation & DGI', 'e-MCeF, TVA, AIB, export', 'gray'],
                                ['admin', 'heroicon-o-cog-6-tooth', 'Administration', 'Utilisateurs, rôles, paramètres', 'gray'],
                            ] as [$sectionKey, $icon, $title, $desc, $color])
                                <button
                                    wire:click="setSection('{{ $sectionKey }}')"
                                    class="flex flex-col items-start gap-3 rounded-xl border border-gray-200 dark:border-white/10 p-4 text-left transition hover:shadow-md hover:border-primary-300 dark:hover:border-primary-500/30"
                                >
                                    <x-dynamic-component :component="$icon" class="h-8 w-8 text-{{ $color }}-500" />
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $title }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $desc }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Concepts clés --}}
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <x-heroicon-o-light-bulb class="inline h-5 w-5 text-yellow-500 mr-1" />
                            Concepts clés
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="rounded-lg bg-blue-50 dark:bg-blue-500/10 p-4">
                                <p class="font-semibold text-blue-800 dark:text-blue-300 mb-1">Multi-entreprise</p>
                                <p class="text-blue-700 dark:text-blue-400">Chaque entreprise a son propre espace isolé (clients, produits, comptabilité). Passez d'une entreprise à l'autre depuis le menu supérieur.</p>
                            </div>
                            <div class="rounded-lg bg-green-50 dark:bg-green-500/10 p-4">
                                <p class="font-semibold text-green-800 dark:text-green-300 mb-1">Multi-entrepôt</p>
                                <p class="text-green-700 dark:text-green-400">Gérez le stock sur plusieurs entrepôts. Chaque vente est liée à un entrepôt source. Les transferts entre entrepôts sont traçables.</p>
                            </div>
                            <div class="rounded-lg bg-purple-50 dark:bg-purple-500/10 p-4">
                                <p class="font-semibold text-purple-800 dark:text-purple-300 mb-1">Rôles & Permissions</p>
                                <p class="text-purple-700 dark:text-purple-400">Les administrateurs voient tout. Les caissiers n'ont accès qu'au point de vente et aux entrepôts assignés.</p>
                            </div>
                            <div class="rounded-lg bg-orange-50 dark:bg-orange-500/10 p-4">
                                <p class="font-semibold text-orange-800 dark:text-orange-300 mb-1">Certification DGI</p>
                                <p class="text-orange-700 dark:text-orange-400">Les factures sont automatiquement certifiées via e-MCeF (NIM, QR code, compteurs). Conforme à la réglementation béninoise.</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- SECTION VENTES --}}
                {{-- ============================================================ --}}
                @if($activeSection === 'sales')
                <div class="space-y-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <x-heroicon-o-shopping-cart class="h-6 w-6 text-primary-500" />
                            Module Ventes
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Gestion complète de vos ventes, clients, devis et bons de livraison.</p>

                        {{-- Créer une vente --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📝 Créer une vente</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Pour créer une nouvelle facture de vente :</p>
                                <ol class="list-decimal list-inside space-y-2 ml-2">
                                    <li><strong>Aller dans Ventes → Ventes</strong> puis cliquer sur <span class="px-2 py-0.5 rounded bg-primary-100 dark:bg-primary-500/20 text-primary-700 dark:text-primary-300 text-xs font-medium">Nouveau</span></li>
                                    <li><strong>Sélectionner le client</strong> — recherche par nom, création rapide possible</li>
                                    <li><strong>Choisir l'entrepôt source</strong> — détermine les produits disponibles</li>
                                    <li><strong>Ajouter les articles</strong> — sélectionnez le produit, la quantité, le prix unitaire HT se remplit automatiquement</li>
                                    <li><strong>Vérifier les totaux</strong> — HT, TVA, TTC sont calculés en temps réel</li>
                                    <li><strong>Choisir le mode de paiement</strong> et le statut (en attente / terminée)</li>
                                    <li><strong>Enregistrer</strong> — la facture reçoit un numéro automatique et est certifiée e-MCeF si activé</li>
                                </ol>
                            </div>
                        </div>

                        {{-- Type de vente --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🏠✈️ Vente locale vs. Export</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Lors de la création d'une vente, un <strong>bouton radio</strong> permet de choisir le type :</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                        <p class="font-semibold mb-1">🏠 Vente locale</p>
                                        <ul class="list-disc list-inside text-xs space-y-1 text-gray-600 dark:text-gray-400">
                                            <li>TVA appliquée selon le groupe fiscal du produit (A=18%, B=0%)</li>
                                            <li>TPS (Groupe E) conservée si applicable</li>
                                            <li>Type e-MCeF : <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">FV</code> (Facture de Vente)</li>
                                        </ul>
                                    </div>
                                    <div class="rounded-lg border border-primary-200 dark:border-primary-500/30 bg-primary-50/50 dark:bg-primary-500/5 p-4">
                                        <p class="font-semibold mb-1">✈️ Vente à l'exportation</p>
                                        <ul class="list-disc list-inside text-xs space-y-1 text-gray-600 dark:text-gray-400">
                                            <li>Tous les articles passent en <strong>Groupe C</strong>, TVA 0%</li>
                                            <li>La TPS (Groupe E) est supprimée automatiquement</li>
                                            <li>Type e-MCeF : <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">EV</code> (Export Vente)</li>
                                            <li>Mention légale : « Exonération de TVA — Art. 262 CGI »</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Avoir (credit note) --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🔄 Créer un avoir (note de crédit)</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Un avoir annule partiellement ou totalement une facture :</p>
                                <ol class="list-decimal list-inside space-y-2 ml-2">
                                    <li>Ouvrir la facture d'origine depuis la liste des ventes</li>
                                    <li>Cliquer sur <strong>« Créer un avoir »</strong> dans les actions</li>
                                    <li>L'avoir fait automatiquement référence à la facture d'origine (exigence DGI)</li>
                                    <li>Le stock est restitué et les montants sont en négatif</li>
                                </ol>
                                <div class="rounded-lg bg-yellow-50 dark:bg-yellow-500/10 p-3 text-xs text-yellow-800 dark:text-yellow-300">
                                    <strong>⚠️ Important :</strong> Un avoir sur une facture certifiée e-MCeF sera aussi certifié (type FA ou EA).
                                </div>
                            </div>
                        </div>

                        {{-- Clients --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">👥 Gestion des clients</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Les clients sont accessibles via <strong>Ventes → Clients</strong>. Informations clés :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li><strong>IFU</strong> — Identifiant Fiscal Unique (obligatoire pour l'AIB). L'IFU est vérifié automatiquement auprès de la DGI.</li>
                                    <li><strong>Adresse, téléphone, email</strong> — apparaissent sur les factures</li>
                                    <li><strong>AIB</strong> — déterminé automatiquement : 1% si le client a un IFU, 5% sinon</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Devis --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📋 Devis</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Créez des devis via <strong>Ventes → Devis</strong>. Un devis peut être :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Envoyé au client par email avec un lien de consultation</li>
                                    <li>Accepté ou refusé en ligne par le client</li>
                                    <li>Converti en facture de vente d'un clic</li>
                                    <li>Téléchargé en PDF</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Bons de livraison --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🚚 Bons de livraison</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Gérez les livraisons via <strong>Ventes → Bons de Livraison</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Liés à une vente existante</li>
                                    <li>Suivi du statut : en préparation, expédié, livré</li>
                                    <li>Imprimables en PDF avec signature</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- SECTION POINT DE VENTE --}}
                {{-- ============================================================ --}}
                @if($activeSection === 'pos')
                <div class="space-y-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <x-heroicon-o-calculator class="h-6 w-6 text-green-500" />
                            Point de Vente (Caisse)
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Interface de caisse rapide pour les ventes au comptoir.</p>

                        {{-- Ouvrir une session --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🔓 Ouvrir une session de caisse</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Avant de commencer les ventes au POS :</p>
                                <ol class="list-decimal list-inside space-y-2 ml-2">
                                    <li>Aller dans <strong>Point de Vente → Caisse</strong></li>
                                    <li>Cliquer sur <strong>« Ouvrir une session »</strong></li>
                                    <li>Indiquer le <strong>fond de caisse initial</strong> (montant en espèces dans le tiroir)</li>
                                    <li>La session est maintenant active — vous pouvez encaisser</li>
                                </ol>
                                <div class="rounded-lg bg-blue-50 dark:bg-blue-500/10 p-3 text-xs text-blue-800 dark:text-blue-300">
                                    <strong>💡 Astuce :</strong> Les caissiers sont automatiquement redirigés vers le POS à la connexion.
                                </div>
                            </div>
                        </div>

                        {{-- Encaisser une vente --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">💰 Encaisser une vente</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <ol class="list-decimal list-inside space-y-2 ml-2">
                                    <li><strong>Scanner le code-barres</strong> ou rechercher le produit par nom</li>
                                    <li>Ajuster la <strong>quantité</strong> si nécessaire</li>
                                    <li>Le total se calcule en temps réel (HT + TVA)</li>
                                    <li>Sélectionner le <strong>mode de paiement</strong> (espèces, carte, Mobile Money…)</li>
                                    <li>Cliquer sur <strong>« Valider »</strong> — la facture est générée et certifiée</li>
                                </ol>
                            </div>
                        </div>

                        {{-- Fermer une session --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🔒 Fermer la session</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>À la fin de la journée :</p>
                                <ol class="list-decimal list-inside space-y-2 ml-2">
                                    <li>Cliquer sur <strong>« Fermer la session »</strong></li>
                                    <li>Compter le tiroir et entrer le <strong>montant réel en caisse</strong></li>
                                    <li>Le système compare avec le montant théorique et affiche l'écart</li>
                                    <li>Un récapitulatif de la session est généré (nombre de ventes, total, ventilation par mode de paiement)</li>
                                </ol>
                            </div>
                        </div>

                        {{-- Historique --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📊 Historique des sessions</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Consultez l'historique via <strong>Point de Vente → Historique Sessions</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Voir chaque session (date, caissier, montants, écart)</li>
                                    <li>Détail des ventes par session</li>
                                    <li>Filtrage par date et par caissier</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- SECTION STOCKS & ACHATS --}}
                {{-- ============================================================ --}}
                @if($activeSection === 'stock')
                <div class="space-y-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <x-heroicon-o-cube class="h-6 w-6 text-yellow-500" />
                            Stocks & Achats
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Gestion des produits, fournisseurs, entrepôts et approvisionnements.</p>

                        {{-- Produits --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📦 Produits</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Créez et gérez vos produits via <strong>Stocks → Produits</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li><strong>Nom, code interne, code-barres</strong> — le code interne est auto-généré</li>
                                    <li><strong>Prix d'achat HT / Prix de vente HT</strong> — les marges sont calculées automatiquement</li>
                                    <li><strong>Prix de gros</strong> — s'active automatiquement à partir d'une quantité minimale</li>
                                    <li><strong>Groupe fiscal</strong> — A (TVA 18%), B (exonéré), C (export), E (TPS)</li>
                                    <li><strong>Taxe spécifique</strong> (Groupe E) — montant fixe par unité (ex: taxe sur boissons)</li>
                                    <li><strong>Fournisseur par défaut</strong> pour les achats</li>
                                </ul>
                                <div class="rounded-lg bg-yellow-50 dark:bg-yellow-500/10 p-3 text-xs text-yellow-800 dark:text-yellow-300">
                                    <strong>⚠️ Important :</strong> Le groupe fiscal détermine le taux de TVA appliqué en vente et le type de facture e-MCeF.
                                </div>
                            </div>
                        </div>

                        {{-- Import produits --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📥 Import de produits</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Importez en masse vos produits via <strong>Stocks → Importer Produits</strong> :</p>
                                <ol class="list-decimal list-inside space-y-2 ml-2">
                                    <li>Télécharger le modèle Excel/CSV</li>
                                    <li>Remplir les colonnes (nom, prix achat, prix vente, code-barres…)</li>
                                    <li>Importer le fichier — les produits sont créés en masse</li>
                                </ol>
                            </div>
                        </div>

                        {{-- Entrepôts --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🏭 Entrepôts</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Configurez vos entrepôts via <strong>Stocks → Entrepôts</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Chaque entrepôt a son propre stock isolé</li>
                                    <li>Un entrepôt <strong>par défaut</strong> est utilisé pour les nouvelles ventes</li>
                                    <li>Les caissiers peuvent être <strong>restreints</strong> à un ou plusieurs entrepôts</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Transferts --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🔄 Transferts de stock</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Déplacez du stock entre entrepôts via <strong>Stocks → Transferts</strong> :</p>
                                <ol class="list-decimal list-inside space-y-2 ml-2">
                                    <li>Sélectionner l'entrepôt <strong>source</strong> et l'entrepôt <strong>destination</strong></li>
                                    <li>Ajouter les produits et quantités à transférer</li>
                                    <li>Valider — le stock se décrémente de la source et s'incrémente dans la destination</li>
                                </ol>
                            </div>
                        </div>

                        {{-- Inventaires --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📋 Inventaires</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Faites des inventaires physiques via <strong>Stocks → Inventaires</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Sélectionner l'entrepôt à inventorier</li>
                                    <li>Compter physiquement et saisir les quantités réelles</li>
                                    <li>Le système calcule les écarts (excédents et manquants)</li>
                                    <li>Valider pour ajuster le stock automatiquement</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Achats --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🛒 Achats / Approvisionnements</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Enregistrez vos achats via <strong>Stocks → Achats</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Sélectionner le fournisseur et l'entrepôt de réception</li>
                                    <li>Ajouter les produits achetés avec quantités et prix d'achat</li>
                                    <li>La validation incrémente le stock de l'entrepôt de réception</li>
                                    <li>Facture d'achat imprimable en PDF</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Fournisseurs --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🏢 Fournisseurs</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Gérez votre répertoire de fournisseurs via <strong>Stocks → Fournisseurs</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Nom, contact, adresse, email, téléphone</li>
                                    <li>Historique des achats avec chaque fournisseur</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- SECTION COMPTABILITÉ --}}
                {{-- ============================================================ --}}
                @if($activeSection === 'accounting')
                <div class="space-y-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <x-heroicon-o-banknotes class="h-6 w-6 text-red-500" />
                            Comptabilité
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Écritures comptables, rapports financiers et conformité fiscale.</p>

                        {{-- Écritures --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📒 Écritures comptables</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Les écritures sont accessibles via <strong>Comptabilité → Écritures</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Les ventes et achats génèrent automatiquement des écritures comptables</li>
                                    <li>Vous pouvez aussi créer des écritures manuelles (OD, salaires…)</li>
                                    <li>Chaque écriture est affectée à une <strong>catégorie comptable</strong> (compte du plan)</li>
                                    <li>Débit / Crédit avec libellé et pièces justificatives</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Catégories --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📂 Catégories & Plan comptable</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Configurez via <strong>Comptabilité → Catégories</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Numéro de compte, libellé, type (produit, charge, actif, passif)</li>
                                    <li>Organisation hiérarchique du plan comptable</li>
                                    <li>Règles d'imputation automatique pour les ventes/achats</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Rapports --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📊 Rapports financiers</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Plusieurs rapports disponibles via <strong>Comptabilité → Centre de rapports</strong> :</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                        <p class="font-semibold text-xs mb-1">📖 Balance Générale</p>
                                        <p class="text-xs text-gray-500">Soldes de tous les comptes sur une période</p>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                        <p class="font-semibold text-xs mb-1">📋 Journal Audit</p>
                                        <p class="text-xs text-gray-500">Toutes les écritures chronologiquement</p>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                        <p class="font-semibold text-xs mb-1">📈 Rapport Ventes</p>
                                        <p class="text-xs text-gray-500">CA, marges, top produits par période</p>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                        <p class="font-semibold text-xs mb-1">🏪 Comparaison Entrepôts</p>
                                        <p class="text-xs text-gray-500">Performance comparée entre sites</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Paiements & Banques --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🏦 Paiements & Comptes bancaires</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li><strong>Paiements</strong> — suivi des règlements clients avec reçus PDF</li>
                                    <li><strong>Comptes bancaires</strong> — rapprochement des opérations</li>
                                    <li><strong>Transactions bancaires</strong> — import et pointage</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Export comptable --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📤 Export comptable</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Exportez vos données via <strong>Comptabilité → Export</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Export FEC (Fichier des Écritures Comptables)</li>
                                    <li>Export Excel par période</li>
                                    <li>Compatible avec les logiciels comptables tiers</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- SECTION RH --}}
                {{-- ============================================================ --}}
                @if($activeSection === 'hr')
                <div class="space-y-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <x-heroicon-o-user-group class="h-6 w-6 text-indigo-500" />
                            Ressources Humaines
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Gestion du personnel, pointage, congés et planification.</p>

                        {{-- Employés --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">👤 Gestion des employés</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Via <strong>RH → Employés</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Fiche complète : nom, poste, contrat, salaire, coordonnées</li>
                                    <li>Photo et documents rattachés</li>
                                    <li>Historique des pointages et congés</li>
                                    <li>Lien avec un compte utilisateur de l'application</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Pointage --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">⏰ Pointage / Présences</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Le pointage fonctionne de 3 manières :</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                                        <p class="text-2xl mb-1">📱</p>
                                        <p class="font-semibold text-xs">QR Code</p>
                                        <p class="text-xs text-gray-500 mt-1">L'employé scanne un QR affiché dans l'établissement</p>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                                        <p class="text-2xl mb-1">📍</p>
                                        <p class="font-semibold text-xs">Géolocalisation</p>
                                        <p class="text-xs text-gray-500 mt-1">Position GPS enregistrée au pointage</p>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                                        <p class="text-2xl mb-1">✋</p>
                                        <p class="font-semibold text-xs">Manuel</p>
                                        <p class="text-xs text-gray-500 mt-1">L'admin saisit manuellement les heures</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Congés --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🏖️ Demandes de congé</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Via <strong>RH → Congés</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>L'employé soumet une demande (dates, type, motif)</li>
                                    <li>Le responsable approuve ou refuse</li>
                                    <li>Compteur de jours restants par type de congé</li>
                                    <li>Calendrier visuel des absences</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Planning --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📅 Planning & Horaires</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Via <strong>RH → Planning</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Créer des plannings hebdomadaires par employé</li>
                                    <li>Vue calendrier interactive (glisser-déposer)</li>
                                    <li>Horaires par défaut par poste</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Commissions --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">💵 Commissions</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Via <strong>RH → Commissions</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Définir des règles de commission (% sur CA, fixe par vente…)</li>
                                    <li>Calcul automatique selon les ventes réalisées par l'employé</li>
                                    <li>Suivi et validation des commissions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- SECTION FACTURATION & DGI --}}
                {{-- ============================================================ --}}
                @if($activeSection === 'invoicing')
                <div class="space-y-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <x-heroicon-o-document-text class="h-6 w-6 text-gray-500" />
                            Facturation & Conformité DGI
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Tout savoir sur la fiscalité béninoise intégrée à FRECORP.</p>

                        {{-- Groupes de taxe --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🏷️ Groupes de taxe DGI</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Chaque produit est classé dans un groupe de taxe e-MCeF :</p>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs border-collapse">
                                        <thead>
                                            <tr class="bg-gray-50 dark:bg-gray-800">
                                                <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left">Groupe</th>
                                                <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left">Description</th>
                                                <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-center">TVA</th>
                                                <th class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-left">Usage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-bold text-blue-600">A</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">TVA standard</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-center">18%</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">La plupart des biens et services</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-bold text-green-600">B</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">Exonéré</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-center">0%</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">Produits exonérés de TVA</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-bold text-purple-600">C</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">Exportation</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-center">0%</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">Ventes à l'exportation (type EV)</td>
                                            </tr>
                                            <tr>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 font-bold text-orange-600">E</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">TPS (Taxe sur Prestations de Services)</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2 text-center">0%</td>
                                                <td class="border border-gray-200 dark:border-gray-700 px-3 py-2">Taxe synthétique payée par l'entreprise (non facturée au client)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="rounded-lg bg-orange-50 dark:bg-orange-500/10 p-3 text-xs text-orange-800 dark:text-orange-300 mt-2">
                                    <strong>💡 TPS (Groupe E) :</strong> La TPS est une taxe synthétique payée globalement par l'entreprise. Elle n'est <u>pas</u> facturée au client. Sur la facture, elle apparaît à 0% — le TTC est égal au HT. Lors d'une vente à l'exportation, l'article TPS passe automatiquement en Groupe C.
                                </div>
                            </div>
                        </div>

                        {{-- e-MCeF --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🔐 Certification e-MCeF</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>La Machine Électronique Certifiée de Facturation (e-MCeF) est obligatoire au Bénin :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Chaque facture est envoyée à la DGI et reçoit un <strong>code MECeF</strong></li>
                                    <li>Un <strong>QR code</strong> de vérification est ajouté sur la facture</li>
                                    <li>Le <strong>NIM</strong> (Numéro d'Identification Machine) est affiché</li>
                                    <li>Des <strong>compteurs</strong> séquentiels garantissent l'intégrité</li>
                                </ul>
                                <p class="mt-2"><strong>Types de facture e-MCeF :</strong></p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    <div class="rounded bg-gray-100 dark:bg-gray-800 p-2 text-center">
                                        <p class="font-bold text-sm">FV</p><p class="text-xs text-gray-500">Facture de Vente</p>
                                    </div>
                                    <div class="rounded bg-gray-100 dark:bg-gray-800 p-2 text-center">
                                        <p class="font-bold text-sm">FA</p><p class="text-xs text-gray-500">Facture d'Avoir</p>
                                    </div>
                                    <div class="rounded bg-gray-100 dark:bg-gray-800 p-2 text-center">
                                        <p class="font-bold text-sm">EV</p><p class="text-xs text-gray-500">Export Vente</p>
                                    </div>
                                    <div class="rounded bg-gray-100 dark:bg-gray-800 p-2 text-center">
                                        <p class="font-bold text-sm">EA</p><p class="text-xs text-gray-500">Export Avoir</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- AIB --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">💰 AIB (Acompte sur Impôt Bénéfices)</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>L'AIB est un prélèvement fiscal obligatoire au Bénin :</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                        <p class="font-semibold mb-1">Taux A — 1%</p>
                                        <p class="text-xs text-gray-500">Client avec IFU (Identifiant Fiscal Unique)</p>
                                        <p class="text-xs text-gray-500">Calculé sur le montant HT</p>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                        <p class="font-semibold mb-1">Taux B — 5%</p>
                                        <p class="text-xs text-gray-500">Client sans IFU</p>
                                        <p class="text-xs text-gray-500">Calculé sur le montant HT</p>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">L'AIB est affiché séparément sur la facture. Le <strong>Net à Payer = TTC + AIB</strong>.</p>
                            </div>
                        </div>

                        {{-- IFU --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🔍 Vérification IFU</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>L'IFU (Identifiant Fiscal Unique) est vérifié automatiquement :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>À la création d'un client, entrez l'IFU</li>
                                    <li>Le système interroge la base DGI pour vérifier sa validité</li>
                                    <li>Le nom officiel et la raison sociale sont récupérés</li>
                                    <li>L'AIB est automatiquement ajusté en conséquence</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Rapport e-MCeF --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📊 Rapport e-MCeF</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Via <strong>Comptabilité → Rapport e-MCeF</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Synthèse des factures certifiées par période</li>
                                    <li>Statuts de certification (succès, erreurs, en attente)</li>
                                    <li>Ventilation par type (FV, FA, EV, EA)</li>
                                    <li>Possibilité de relancer la certification des factures en erreur</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- SECTION ADMINISTRATION --}}
                {{-- ============================================================ --}}
                @if($activeSection === 'admin')
                <div class="space-y-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <x-heroicon-o-cog-6-tooth class="h-6 w-6 text-gray-500" />
                            Administration
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Configuration de l'application, utilisateurs et sécurité.</p>

                        {{-- Utilisateurs --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">👥 Utilisateurs & Rôles</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Via <strong>Administration → Utilisateurs</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Créer des comptes utilisateur avec email et mot de passe</li>
                                    <li>Attribuer un <strong>rôle</strong> (admin, caissier, comptable…)</li>
                                    <li>Restreindre l'accès à un ou plusieurs <strong>entrepôts</strong></li>
                                    <li>Activer/désactiver des comptes</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Invitations --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">✉️ Invitations d'équipe</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Via <strong>Administration → Invitations</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Envoyez une invitation par email</li>
                                    <li>Le destinataire reçoit un lien unique pour créer son compte</li>
                                    <li>Son rôle et ses permissions sont pré-configurés</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Journal d'activité --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">📋 Journal d'activité</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Via <strong>Administration → Journal d'activité</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Historique complet de toutes les actions (créations, modifications, suppressions)</li>
                                    <li>Qui a fait quoi, quand</li>
                                    <li>Valeurs avant/après pour chaque modification</li>
                                    <li>Filtrable par utilisateur, modèle et date</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Paramètres entreprise --}}
                        <div class="mb-8">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🏢 Paramètres de l'entreprise</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <p>Configurez votre entreprise depuis le <strong>menu supérieur → profil entreprise</strong> :</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li><strong>Logo</strong> — affiché sur toutes les factures</li>
                                    <li><strong>Adresse, téléphone, email</strong></li>
                                    <li><strong>N° Fiscal / SIRET</strong></li>
                                    <li><strong>Devise</strong> (XOF par défaut)</li>
                                    <li><strong>e-MCeF</strong> — token, NIM, mode sandbox/production</li>
                                    <li><strong>AIB</strong> — mode auto/manuel/désactivé</li>
                                    <li><strong>Texte de pied de facture</strong> personnalisable</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Sécurité --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 border-b border-gray-200 dark:border-gray-700 pb-2">🔒 Sécurité</h3>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3">
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Les factures terminées sont <strong>verrouillées</strong> (non modifiables)</li>
                                    <li>Hash de sécurité NF525 sur chaque facture (chaîne d'intégrité)</li>
                                    <li>Journal d'audit complet et non effaçable</li>
                                    <li>Sessions de caisse tracées et auditables</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- VIDÉOS TUTORIELS (affiché sous chaque section si videos dispo) --}}
                {{-- ============================================================ --}}
                @php
                    $sectionVideos = $this->getSectionVideos();
                @endphp
                @if($sectionVideos->count() > 0)
                <div class="mt-6">
                    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <x-heroicon-o-play-circle class="h-5 w-5 text-red-500" />
                            Vidéos tutoriels
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($sectionVideos as $video)
                            <div class="rounded-xl border border-gray-200 dark:border-white/10 overflow-hidden hover:shadow-md transition-shadow">
                                <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;">
                                    <iframe
                                        src="{{ $video->embed_url }}"
                                        style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"
                                        allowfullscreen
                                        loading="lazy"
                                    ></iframe>
                                </div>
                                <div class="p-4">
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $video->title }}</h4>
                                    @if($video->description)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $video->description }}</p>
                                    @endif
                                    @if($video->formatted_duration)
                                        <span class="inline-flex items-center gap-1 mt-2 text-xs text-gray-400 dark:text-gray-500">
                                            <x-heroicon-m-clock class="h-3 w-3" />
                                            {{ $video->formatted_duration }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</x-filament-panels::page>
