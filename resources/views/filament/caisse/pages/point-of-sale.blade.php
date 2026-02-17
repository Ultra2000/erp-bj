<x-filament-panels::page>
    <div 
        x-data="pointOfSale()" 
        x-init="init()"
        class="h-[calc(100vh-180px)] flex flex-col"
        @keydown.f2.window="focusSearch()"
        @keydown.f3.window="focusBarcode()"
        @keydown.f12.window="submitSale()"
        @keydown.escape.window="clearCart()"
        @keydown.window="handleBarcodeScanner($event)"
    >
        {{-- Barre d'état session --}}
        <template x-if="!hasSession">
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="font-medium text-amber-800">Aucune session de caisse ouverte</span>
                </div>
                <a href="{{ route('filament.caisse.pages.cash-session-page', ['tenant' => \Filament\Facades\Filament::getTenant()]) }}" 
                   class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
                    Ouvrir une session
                </a>
            </div>
        </template>

        {{-- Interface principale --}}
        <div class="flex-1 grid grid-cols-12 gap-4 min-h-0">
            {{-- Panneau gauche: Produits --}}
            <div class="col-span-7 flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden">
                {{-- Barre de recherche --}}
                <div class="p-4 border-b bg-gray-50">
                    <div class="flex gap-3">
                        <div class="flex-1 relative">
                            <input 
                                x-ref="searchInput"
                                x-model="searchQuery"
                                @input.debounce.300ms="searchProducts()"
                                type="text" 
                                placeholder="🔍 Rechercher un produit (F2)..."
                                class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-lg"
                            >
                            <button @click="searchQuery = ''; products = []" x-show="searchQuery" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                ✕
                            </button>
                        </div>
                        <div class="relative">
                            <input 
                                x-ref="barcodeInput"
                                @keydown.enter.prevent="scanBarcode($event.target.value); $event.target.value = ''"
                                type="text"
                                placeholder="Code-barres (F3)"
                                class="w-48 px-4 py-3 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-emerald-50 text-lg"
                            >
                        </div>
                        <button @click="openCameraScanner()" class="px-4 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Scanner
                        </button>
                    </div>
                </div>

                {{-- Grille de produits --}}
                <div class="flex-1 overflow-y-auto p-4">
                    <template x-if="loading">
                        <div class="flex items-center justify-center h-full">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
                        </div>
                    </template>
                    
                    <template x-if="!loading && products.length === 0 && searchQuery">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-lg">Aucun produit trouvé</p>
                        </div>
                    </template>

                    <template x-if="!loading && products.length === 0 && !searchQuery">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <p class="text-lg">Recherchez un produit ou scannez un code-barres</p>
                            <p class="text-sm mt-2">Raccourcis: F2 (recherche), F3 (code-barres), F12 (encaisser)</p>
                        </div>
                    </template>

                    <div class="grid grid-cols-3 gap-3" x-show="products.length > 0">
                        <template x-for="product in products" :key="product.id">
                            <button 
                                @click="addToCart(product)"
                                class="p-4 bg-gradient-to-br from-gray-50 to-white border-2 rounded-xl hover:border-emerald-400 hover:shadow-md transition-all text-left group"
                                :class="{'border-red-300 bg-red-50': product.stock <= (product.min_stock || 0)}"
                            >
                                <div class="font-semibold text-gray-800 truncate group-hover:text-emerald-700" x-text="product.name"></div>
                                <div class="text-xs text-gray-500 mt-1" x-text="product.code || 'Sans code'"></div>
                                <div class="flex justify-between items-end mt-3">
                                    <div class="text-lg font-bold text-emerald-600" x-text="formatPrice(Math.round((parseFloat(product.sale_price_ht) || parseFloat(product.price)) * (1 + (parseFloat(product.vat_rate_sale) || 18) / 100)))"></div>
                                    <div class="text-xs px-2 py-1 rounded-full" 
                                         :class="product.stock <= (product.min_stock || 0) ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                         x-text="'Stock: ' + product.stock">
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Panneau droit: Panier --}}
            <div class="col-span-5 flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden">
                {{-- En-tête panier --}}
                <div class="p-4 border-b bg-gradient-to-r from-emerald-600 to-emerald-700 text-white">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Panier
                        </h2>
                        <button @click="clearCart()" x-show="cart.length > 0" class="text-white/80 hover:text-white text-sm">
                            Vider (Esc)
                        </button>
                    </div>
                    
                    {{-- Stats Session --}}
                    <div class="mt-2 text-emerald-100 text-xs flex justify-between items-center" x-show="hasSession">
                        <span x-text="cart.length + ' article(s)'"></span>
                        <div class="flex gap-3">
                            <span x-text="sessionStats.sales_count + ' ventes'"></span>
                            <span class="font-bold" x-text="formatPrice(sessionStats.total_sales)"></span>
                        </div>
                    </div>
                </div>

                {{-- Liste articles --}}
                <div class="flex-1 overflow-y-auto">
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 p-8">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <p>Panier vide</p>
                        </div>
                    </template>

                    <ul class="divide-y">
                        <template x-for="(item, index) in cart" :key="item.id">
                            <li class="p-4 hover:bg-gray-50">
                                <div class="flex justify-between">
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.name"></div>
                                        <div class="text-sm text-gray-500" x-text="formatPrice(item.display_price || item.unit_price) + ' × ' + item.quantity"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-emerald-600" x-text="formatPrice(item.total)"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <button @click="decrementQty(index)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center">−</button>
                                    <input type="number" x-model.number="item.quantity" @change="updateItemTotal(index)" min="0.001" step="any" class="w-16 text-center border rounded py-1">
                                    <button @click="incrementQty(index)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center">+</button>
                                    <button @click="removeFromCart(index)" class="ml-auto text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Résumé et paiement --}}
                <div class="border-t bg-gray-50 p-4 space-y-3">
                    {{-- Sous-total, remise, TVA --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Sous-total</span>
                            <span x-text="formatPrice(subtotal)"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Remise (%)</span>
                            <input type="number" x-model.number="discount" min="0" max="100" step="0.5" class="w-20 text-right border rounded px-2 py-1 text-sm">
                        </div>
                        <div class="flex justify-between items-center text-xs text-gray-400">
                            <span>TVA calculée par produit</span>
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="flex justify-between items-center py-3 border-t border-b">
                        <span class="text-xl font-bold">TOTAL</span>
                        <span class="text-2xl font-bold text-emerald-600" x-text="formatPrice(grandTotal)"></span>
                    </div>

                    {{-- Mode de paiement --}}
                    <div class="grid grid-cols-4 gap-2">
                        <button @click="paymentMethod = 'cash'" 
                                class="py-2 px-3 rounded-lg border-2 transition text-sm font-medium"
                                :class="paymentMethod === 'cash' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'">
                            💵 Espèces
                        </button>
                        <button @click="paymentMethod = 'card'" 
                                class="py-2 px-3 rounded-lg border-2 transition text-sm font-medium"
                                :class="paymentMethod === 'card' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'">
                            💳 Carte
                        </button>
                        <button @click="paymentMethod = 'mobile'" 
                                class="py-2 px-3 rounded-lg border-2 transition text-sm font-medium"
                                :class="paymentMethod === 'mobile' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'">
                            📱 Mobile
                        </button>
                        <button @click="paymentMethod = 'mixed'" 
                                class="py-2 px-3 rounded-lg border-2 transition text-sm font-medium"
                                :class="paymentMethod === 'mixed' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'">
                            🔀 Mixte
                        </button>
                    </div>

                    {{-- Montant reçu (espèces) --}}
                    <template x-if="paymentMethod === 'cash'">
                        <div class="flex gap-3 items-center">
                            <div class="flex-1">
                                <label class="text-xs text-gray-500">Montant reçu</label>
                                <input type="number" x-model.number="receivedAmount" min="0" class="w-full border rounded px-3 py-2 text-lg">
                            </div>
                            <div class="flex-1">
                                <label class="text-xs text-gray-500">Monnaie à rendre</label>
                                <div class="text-2xl font-bold" :class="change >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formatPrice(Math.max(0, change))"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Paiement mixte : répartition --}}
                    <template x-if="paymentMethod === 'mixed'">
                        <div class="space-y-2">
                            <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Répartition du paiement</div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500">💵 Espèces</label>
                                    <input type="number" x-model.number="mixedCash" min="0" @input="updateMixedTotal()" class="w-full border rounded px-2 py-1.5 text-sm" placeholder="0">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">💳 Carte</label>
                                    <input type="number" x-model.number="mixedCard" min="0" @input="updateMixedTotal()" class="w-full border rounded px-2 py-1.5 text-sm" placeholder="0">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">📱 Mobile</label>
                                    <input type="number" x-model.number="mixedMobile" min="0" @input="updateMixedTotal()" class="w-full border rounded px-2 py-1.5 text-sm" placeholder="0">
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-sm pt-1 border-t">
                                <span class="text-gray-500">Total saisi</span>
                                <span class="font-bold" :class="mixedTotal >= grandTotal ? 'text-emerald-600' : 'text-red-600'" x-text="formatPrice(mixedTotal) + ' / ' + formatPrice(grandTotal)"></span>
                            </div>
                            <template x-if="mixedTotal > grandTotal">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500">Monnaie à rendre</span>
                                    <span class="font-bold text-emerald-600" x-text="formatPrice(mixedTotal - grandTotal)"></span>
                                </div>
                            </template>
                            <template x-if="mixedTotal < grandTotal">
                                <div class="text-xs text-red-500 text-center">⚠ Il manque <span x-text="formatPrice(grandTotal - mixedTotal)"></span></div>
                            </template>
                        </div>
                    </template>

                    {{-- Bouton encaisser --}}
                    <button 
                        @click="submitSale()"
                        :disabled="cart.length === 0 || saving || !hasSession"
                        class="w-full py-4 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white text-xl font-bold rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3"
                    >
                        <template x-if="!saving">
                            <span>
                                <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                ENCAISSER (F12)
                            </span>
                        </template>
                        <template x-if="saving">
                            <span>
                                <svg class="animate-spin w-6 h-6 inline mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Traitement...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Scanner Caméra (html5-qrcode) --}}
        <div x-show="showCameraModal" x-cloak class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="bg-emerald-600 text-white p-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold">📷 Scanner code-barres</h3>
                    <button @click="closeCameraScanner()" class="hover:bg-emerald-700 rounded-full p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <div id="qr-reader" style="min-height: 300px; border-radius: 8px; overflow: hidden;"></div>
                    <div class="mt-4 text-center text-sm" :class="lastScannedCode ? 'text-emerald-600 font-semibold' : 'text-gray-500'" x-text="lastScannedCode ? '✓ Dernier code: ' + lastScannedCode : 'Placez le code-barres dans le cadre'"></div>
                    <div x-show="cameraStatus" class="mt-2 text-center text-xs text-gray-400" x-text="cameraStatus"></div>
                </div>
            </div>
        </div>

        {{-- Notification succès --}}
        <template x-if="showSuccess">
            <div class="fixed top-4 right-4 bg-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg z-50 animate-bounce">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <div class="font-bold">Vente enregistrée !</div>
                        <div class="text-sm" x-text="'Ticket #' + lastSaleId"></div>
                    </div>
                </div>
            </div>
        </template>
        
        {{-- Indicateur de scan douchette --}}
        <template x-if="scannerBuffer.length > 0">
            <div class="fixed top-4 left-1/2 -translate-x-1/2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2">
                <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
                <span class="font-mono" x-text="'Scan: ' + scannerBuffer"></span>
            </div>
        </template>
        
        {{-- Message de scan (succès/erreur) --}}
        <template x-if="showScanMessage">
            <div 
                class="fixed top-20 left-1/2 -translate-x-1/2 px-6 py-3 rounded-lg shadow-xl z-50 flex items-center gap-3 font-medium text-lg transition-all"
                :class="{
                    'bg-emerald-600 text-white': scanMessageType === 'success',
                    'bg-red-600 text-white': scanMessageType === 'error'
                }"
            >
                <svg x-show="scanMessageType === 'success'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <svg x-show="scanMessageType === 'error'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span x-text="scanMessage"></span>
            </div>
        </template>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        function pointOfSale() {
            return {
                // État
                searchQuery: '',
                products: [],
                cart: [],
                loading: false,
                saving: false,
                hasSession: false,
                
                // Paiement
                discount: 0,
                paymentMethod: 'cash',
                receivedAmount: 0,
                
                // Paiement mixte
                mixedCash: 0,
                mixedCard: 0,
                mixedMobile: 0,
                mixedTotal: 0,
                
                // Scanner
                showCameraModal: false,
                lastScannedCode: '',
                cameraStatus: '',
                html5QrCode: null,
                
                // Détection douchette automatique
                scannerBuffer: '',
                scannerTimeout: null,
                scannerLastKeyTime: 0,
                
                // Feedback scan
                scanMessage: '',
                scanMessageType: '', // 'success' ou 'error'
                showScanMessage: false,
                
                // Feedback
                showSuccess: false,
                lastSaleId: null,
                
                // Session stats
                sessionStats: {
                    sales_count: 0,
                    total_sales: 0
                },

                init() {
                    this.checkSession();
                    this.focusSearch();
                },

                async checkSession() {
                    const response = await this.$wire.hasOpenSession();
                    this.hasSession = response;
                    if (this.hasSession) {
                        this.refreshSessionStats();
                    }
                },

                async refreshSessionStats() {
                    const stats = await this.$wire.getOpenSessionStats();
                    if (stats) {
                        this.sessionStats = stats;
                    }
                },

                formatPrice(value) {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(value) + ' FCFA';
                },

                focusSearch() {
                    this.$refs.searchInput?.focus();
                },

                focusBarcode() {
                    this.$refs.barcodeInput?.focus();
                },
                
                // === Paiement mixte ===
                updateMixedTotal() {
                    this.mixedTotal = (this.mixedCash || 0) + (this.mixedCard || 0) + (this.mixedMobile || 0);
                },
                
                /**
                 * Détection automatique de la douchette USB
                 */
                handleBarcodeScanner(event) {
                    const target = event.target;
                    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) {
                        return;
                    }
                    
                    if (event.ctrlKey || event.altKey || event.metaKey || event.key.length > 1 && event.key !== 'Enter') {
                        return;
                    }
                    
                    const now = Date.now();
                    const timeSinceLastKey = now - this.scannerLastKeyTime;
                    
                    if (event.key === 'Enter' && this.scannerBuffer.length > 0) {
                        event.preventDefault();
                        const scannedCode = this.scannerBuffer.trim();
                        this.scannerBuffer = '';
                        
                        if (scannedCode.length >= 3) {
                            this.scanBarcode(scannedCode);
                        }
                        return;
                    }
                    
                    if (timeSinceLastKey > 100) {
                        this.scannerBuffer = '';
                    }
                    
                    if (event.key.length === 1 && timeSinceLastKey < 100) {
                        event.preventDefault();
                        this.scannerBuffer += event.key;
                        this.scannerLastKeyTime = now;
                        
                        clearTimeout(this.scannerTimeout);
                        this.scannerTimeout = setTimeout(() => {
                            if (this.scannerBuffer.length >= 3) {
                                this.scanBarcode(this.scannerBuffer.trim());
                            }
                            this.scannerBuffer = '';
                        }, 200);
                    }
                },

                async searchProducts() {
                    if (!this.searchQuery.trim()) {
                        this.products = [];
                        return;
                    }
                    this.loading = true;
                    try {
                        this.products = await this.$wire.searchProducts(this.searchQuery);
                    } finally {
                        this.loading = false;
                    }
                },

                async scanBarcode(code) {
                    if (!code.trim()) return;
                    
                    console.log('🔍 Recherche code-barres:', code);
                    
                    const product = await this.$wire.getProductByBarcode(code.trim());
                    if (product) {
                        this.addToCart(product);
                        this.playBeep(true);
                        this.showScanSuccess(product.name);
                    } else {
                        this.playBeep(false);
                        this.showScanError('Produit non trouvé: ' + code);
                    }
                },

                addToCart(product) {
                    const existing = this.cart.find(i => i.id === product.id);
                    if (existing) {
                        if (existing.quantity < product.stock) {
                            existing.quantity++;
                            existing.total = existing.quantity * existing.display_price;
                        } else {
                            alert('Stock insuffisant');
                        }
                    } else {
                        const priceHt = parseFloat(product.sale_price_ht) || parseFloat(product.price);
                        const vatRate = parseFloat(product.vat_rate_sale) || 18;
                        const priceTtc = Math.round(priceHt * (1 + vatRate / 100));
                        
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            unit_price: priceHt,
                            display_price: priceTtc,
                            vat_rate: vatRate,
                            quantity: 1,
                            total: priceTtc,
                            stock: product.stock
                        });
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                incrementQty(index) {
                    const item = this.cart[index];
                    if (item.quantity < item.stock) {
                        item.quantity++;
                        this.updateItemTotal(index);
                    }
                },

                decrementQty(index) {
                    const item = this.cart[index];
                    if (item.quantity > 1) {
                        item.quantity--;
                        this.updateItemTotal(index);
                    }
                },

                updateItemTotal(index) {
                    const item = this.cart[index];
                    item.total = item.quantity * item.display_price;
                },

                clearCart() {
                    this.cart = [];
                    this.discount = 0;
                    this.receivedAmount = 0;
                    this.mixedCash = 0;
                    this.mixedCard = 0;
                    this.mixedMobile = 0;
                    this.mixedTotal = 0;
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + item.total, 0);
                },

                get grandTotal() {
                    const discountAmt = this.subtotal * (this.discount / 100);
                    const afterDiscount = this.subtotal - discountAmt;
                    return Math.round(afterDiscount);
                },

                get change() {
                    return this.receivedAmount - this.grandTotal;
                },

                async submitSale() {
                    if (this.cart.length === 0 || !this.hasSession) return;
                    
                    // Validation paiement mixte
                    if (this.paymentMethod === 'mixed') {
                        this.updateMixedTotal();
                        if (this.mixedTotal < this.grandTotal) {
                            alert('Le total du paiement mixte (' + this.formatPrice(this.mixedTotal) + ') est inférieur au total de la vente (' + this.formatPrice(this.grandTotal) + ').');
                            return;
                        }
                    }
                    
                    this.saving = true;
                    try {
                        const stockCheck = await this.$wire.verifyCartStock(this.cart.map(i => ({
                            product_id: i.id,
                            quantity: i.quantity
                        })));
                        
                        if (!stockCheck.valid) {
                            alert('Stock insuffisant pour : ' + stockCheck.errors.join(', '));
                            if (stockCheck.updatedStocks) {
                                for (const [productId, newStock] of Object.entries(stockCheck.updatedStocks)) {
                                    const item = this.cart.find(i => i.id == productId);
                                    if (item) item.stock = newStock;
                                }
                            }
                            this.saving = false;
                            return;
                        }
                        
                        // Construire les détails de paiement
                        let paymentDetails = null;
                        if (this.paymentMethod === 'mixed') {
                            paymentDetails = {
                                cash: this.mixedCash || 0,
                                card: this.mixedCard || 0,
                                mobile: this.mixedMobile || 0,
                            };
                        }
                        
                        const result = await this.$wire.recordSale({
                            items: this.cart.map(i => ({
                                product_id: i.id,
                                quantity: i.quantity,
                                unit_price: i.unit_price
                            })),
                            discount_percent: this.discount,
                            payment_method: this.paymentMethod,
                            payment_details: paymentDetails
                        });

                        if (result.success) {
                            this.lastSaleId = result.invoice_number;
                            this.showSuccess = true;
                            setTimeout(() => this.showSuccess = false, 3000);
                            this.clearCart();
                            this.refreshSessionStats();
                            this.playBeep();
                        } else {
                            alert(result.message || 'Erreur lors de la vente');
                        }
                    } finally {
                        this.saving = false;
                    }
                },

                // === Scanner caméra avec html5-qrcode ===
                async openCameraScanner() {
                    this.showCameraModal = true;
                    this.cameraStatus = 'Démarrage de la caméra...';
                    await this.$nextTick();
                    
                    try {
                        this.html5QrCode = new Html5Qrcode("qr-reader");
                        
                        const config = {
                            fps: 10,
                            qrbox: { width: 280, height: 120 },
                            aspectRatio: 1.5,
                            formatsToSupport: [
                                Html5QrcodeSupportedFormats.EAN_13,
                                Html5QrcodeSupportedFormats.EAN_8,
                                Html5QrcodeSupportedFormats.UPC_A,
                                Html5QrcodeSupportedFormats.UPC_E,
                                Html5QrcodeSupportedFormats.CODE_128,
                                Html5QrcodeSupportedFormats.CODE_39,
                                Html5QrcodeSupportedFormats.CODE_93,
                                Html5QrcodeSupportedFormats.ITF,
                                Html5QrcodeSupportedFormats.QR_CODE,
                            ]
                        };
                        
                        await this.html5QrCode.start(
                            { facingMode: "environment" },
                            config,
                            (decodedText) => {
                                // Code-barres détecté !
                                this.lastScannedCode = decodedText;
                                this.playBeep(true);
                                this.scanBarcode(decodedText);
                                
                                // Pause brève pour éviter les doublons
                                this.html5QrCode.pause(true);
                                setTimeout(() => {
                                    if (this.html5QrCode && this.showCameraModal) {
                                        try { this.html5QrCode.resume(); } catch(e) {}
                                    }
                                }, 1500);
                            },
                            (errorMessage) => {
                                // Scan en cours, pas de code trouvé - silencieux
                            }
                        );
                        
                        this.cameraStatus = 'Caméra active — placez le code-barres dans le cadre';
                        
                    } catch (err) {
                        console.error('Camera error:', err);
                        let message = 'Erreur caméra';
                        if (err.toString().includes('NotAllowedError')) {
                            message = 'Accès à la caméra refusé. Autorisez dans les paramètres du navigateur.';
                        } else if (err.toString().includes('NotFoundError')) {
                            message = 'Aucune caméra trouvée sur cet appareil.';
                        } else {
                            message = 'Erreur: ' + err;
                        }
                        this.cameraStatus = message;
                    }
                },

                async closeCameraScanner() {
                    if (this.html5QrCode) {
                        try {
                            await this.html5QrCode.stop();
                        } catch(e) {}
                        this.html5QrCode = null;
                    }
                    this.showCameraModal = false;
                    this.cameraStatus = '';
                },

                playBeep(success = true) {
                    if (navigator.vibrate) {
                        navigator.vibrate(success ? 50 : [100, 50, 100]);
                    }
                    
                    try {
                        if (!this.audioContext) {
                            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        }
                        
                        if (this.audioContext.state === 'suspended') {
                            this.audioContext.resume();
                        }
                        
                        const oscillator = this.audioContext.createOscillator();
                        const gainNode = this.audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(this.audioContext.destination);
                        
                        oscillator.frequency.value = success ? 800 : 400;
                        oscillator.type = 'sine';
                        
                        gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.1);
                        
                        oscillator.start(this.audioContext.currentTime);
                        oscillator.stop(this.audioContext.currentTime + 0.1);
                    } catch (e) {}
                },
                
                showScanSuccess(productName) {
                    this.scanMessage = '✓ ' + productName + ' ajouté';
                    this.scanMessageType = 'success';
                    this.showScanMessage = true;
                    setTimeout(() => {
                        this.showScanMessage = false;
                    }, 2000);
                },
                
                showScanError(message) {
                    this.scanMessage = '✗ ' + message;
                    this.scanMessageType = 'error';
                    this.showScanMessage = true;
                    setTimeout(() => {
                        this.showScanMessage = false;
                    }, 3000);
                }
            };
        }
    </script>
</x-filament-panels::page>
