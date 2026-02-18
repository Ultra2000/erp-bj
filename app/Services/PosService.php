<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Service centralisé pour toute la logique métier du Point de Vente (POS).
 * 
 * Élimine la double implémentation CaisseController / CashRegisterPage.
 * Implémentation canonique : warehouse-aware, FIFO destock, sale_price_ht.
 */
class PosService
{
    // ──────────────────────────────────────────────
    //  WAREHOUSE RESOLUTION (4-level priority)
    // ──────────────────────────────────────────────

    /**
     * Résout l'entrepôt POS pour un utilisateur donné.
     * Priorité : assigné > POS > défaut > n'importe lequel.
     */
    public function resolveWarehouse(int $companyId, ?int $userId = null): ?Warehouse
    {
        $user = $userId ? \App\Models\User::find($userId) : auth()->user();
        if (!$user) return null;

        // 1. Entrepôt assigné à l'utilisateur
        $userWarehouse = $user->warehouses()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByPivot('is_default', 'desc')
            ->first();
        if ($userWarehouse) return $userWarehouse;

        // 2. Entrepôt désigné POS
        $posWarehouse = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_pos_location', true)
            ->first();
        if ($posWarehouse) return $posWarehouse;

        // 3. Entrepôt par défaut
        $defaultWarehouse = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
        if ($defaultWarehouse) return $defaultWarehouse;

        // 4. N'importe quel entrepôt actif
        return Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();
    }

    // ──────────────────────────────────────────────
    //  SESSION MANAGEMENT
    // ──────────────────────────────────────────────

    /**
     * Récupère la session ouverte pour un utilisateur.
     */
    public function getOpenSession(int $companyId, int $userId): ?CashSession
    {
        return CashSession::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Vérifie le statut de la session et retourne les statistiques.
     */
    public function checkSession(int $companyId, int $userId): array
    {
        $session = $this->getOpenSession($companyId, $userId);

        if (!$session) {
            return ['open' => false, 'session' => null];
        }

        $session->recalculate();

        return [
            'open' => true,
            'session' => $this->formatSessionStats($session),
        ];
    }

    /**
     * Ouvre une nouvelle session de caisse.
     */
    public function openSession(int $companyId, int $userId, float $openingAmount = 0): array
    {
        $existing = $this->getOpenSession($companyId, $userId);
        if ($existing) {
            return ['success' => false, 'message' => 'Une session est déjà ouverte'];
        }

        // Utilise le modèle CashSession::openSession qui positionne correctement le status
        $session = CashSession::openSession($companyId, $userId, $openingAmount);

        return [
            'success' => true,
            'session' => $this->formatSessionStats($session),
        ];
    }

    /**
     * Ferme la session de caisse.
     * 
     * Implémente la « Clôture Aveugle » : le montant attendu et l'écart
     * ne sont révélés qu'APRÈS que le caissier ait soumis son comptage.
     * Les données de comparaison sont retournées dans blind_count_result.
     */
    public function closeSession(int $companyId, int $userId, float $closingAmount, ?string $notes = null): array
    {
        $session = $this->getOpenSession($companyId, $userId);
        if (!$session) {
            return ['success' => false, 'message' => 'Aucune session ouverte'];
        }

        // Utilise le modèle qui : recalculate() → met à jour status='closed'
        $session->closeSession($closingAmount, $notes);

        return [
            'success' => true,
            'session' => $session,
            'difference' => floatval($session->difference),
            // Résultats de la clôture aveugle — révélés APRÈS soumission
            'blind_count_result' => [
                'opening_amount' => floatval($session->opening_amount),
                'total_sales' => floatval($session->total_sales),
                'sales_count' => intval($session->sales_count),
                'cash_sales' => floatval($session->total_cash),
                'card_sales' => floatval($session->total_card),
                'mobile_sales' => floatval($session->total_mobile),
                'expected_cash' => floatval($session->expected_amount),
                'counted' => $closingAmount,
                'difference' => floatval($session->difference),
            ],
        ];
    }

    // ──────────────────────────────────────────────
    //  PRODUCT QUERIES (warehouse-aware)
    // ──────────────────────────────────────────────

    /**
     * Liste les produits disponibles pour le POS.
     */
    public function getProducts(int $companyId, ?Warehouse $warehouse = null, int $limit = 50): array
    {
        if ($warehouse) {
            return $warehouse->products()
                ->where('product_warehouse.quantity', '>', 0)
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code,
                    'selling_price' => $p->price,
                    'quantity' => $p->pivot->quantity,
                ])
                ->toArray();
        }

        return Product::where('company_id', $companyId)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'price', 'stock'])
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'selling_price' => $p->price,
                'quantity' => $p->stock,
            ])
            ->toArray();
    }

    /**
     * Recherche de produits par nom ou code.
     */
    public function searchProducts(int $companyId, string $query, ?Warehouse $warehouse = null, int $limit = 25): array
    {
        if (strlen($query) < 1) return [];

        if ($warehouse) {
            return $warehouse->products()
                ->where(function ($q) use ($query) {
                    $q->where('products.name', 'like', "%{$query}%")
                      ->orWhere('products.code', 'like', "%{$query}%");
                })
                ->where('product_warehouse.quantity', '>', 0)
                ->orderBy('products.name')
                ->limit($limit)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code,
                    'selling_price' => $p->price,
                    'quantity' => $p->pivot->quantity,
                ])
                ->toArray();
        }

        return Product::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'price', 'stock'])
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'selling_price' => $p->price,
                'quantity' => $p->stock,
            ])
            ->toArray();
    }

    /**
     * Recherche un produit par code-barres.
     */
    public function getProductByBarcode(int $companyId, string $code, ?Warehouse $warehouse = null): ?array
    {
        if ($warehouse) {
            $product = $warehouse->products()
                ->where('products.code', $code)
                ->first();

            if (!$product) return null;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'selling_price' => $product->price,
                'quantity' => $product->pivot->quantity,
            ];
        }

        $product = Product::where('company_id', $companyId)
            ->where('code', $code)
            ->first(['id', 'name', 'code', 'price', 'stock']);

        if (!$product) return null;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'selling_price' => $product->price,
            'quantity' => $product->stock,
        ];
    }

    // ──────────────────────────────────────────────
    //  SALE RECORDING (canonical implementation)
    // ──────────────────────────────────────────────

    /**
     * Enregistre une vente POS.
     * 
     * Implémentation canonique unique :
     * - Utilise sale_price_ht pour éviter la double taxation
     * - Vérifie le stock au niveau entrepôt (warehouse-aware)
     * - Déstockage FIFO via warehouse->deductStockFIFO()
     * - Force les totaux post-observers
     * - Certification e-MCeF hors transaction
     */
    public function recordSale(
        int $companyId,
        CashSession $session,
        array $items,
        string $paymentMethod = 'cash',
        ?array $paymentDetails = null,
        ?int $customerId = null,
        float $discountPercent = 0,
        ?Warehouse $warehouse = null
    ): array {
        if (empty($items)) {
            return ['success' => false, 'message' => 'Le panier est vide'];
        }

        $company = Company::find($companyId);

        try {
            // ── 1. Pré-calculer les totaux AVANT toute opération ──
            $itemsToCreate = [];
            $totalHt = 0;
            $totalVat = 0;
            $totalTtc = 0;

            foreach ($items as $item) {
                $product = Product::where('company_id', $companyId)->findOrFail($item['product_id']);
                $qty = floatval($item['quantity']);
                if ($qty <= 0) $qty = 1;

                // Toujours utiliser sale_price_ht (garanti HT) pour éviter la double taxation
                $price = floatval($product->sale_price_ht ?? $product->price);
                $vatRate = $product->vat_rate_sale ?? 18;

                // Vérification du stock au niveau entrepôt
                if ($warehouse) {
                    $stock = DB::table('product_warehouse')
                        ->where('product_id', $product->id)
                        ->where('warehouse_id', $warehouse->id)
                        ->sum('quantity') ?? 0;

                    if ($stock < $qty) {
                        throw new \RuntimeException("Stock insuffisant pour {$product->name}");
                    }
                }

                // Calculs : TVA + taxe spécifique cumulatives
                $lineHt = $qty * $price;
                $lineVat = round($lineHt * ($vatRate / 100), 2);
                $lineTaxSpec = ($product->tax_specific_amount > 0)
                    ? round($product->tax_specific_amount * $qty, 2)
                    : 0;
                $lineTtc = $lineHt + $lineVat + $lineTaxSpec;

                $totalHt += $lineHt;
                $totalVat += $lineVat;
                $totalTtc += $lineTtc;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'unit_price_ht' => $price,
                    'vat_rate' => $vatRate,
                    'vat_category' => $product->vat_category ?? 'S',
                    'tax_specific_amount' => $product->tax_specific_amount,
                    'tax_specific_label' => $product->tax_specific_label,
                    'tax_specific_total' => $lineTaxSpec,
                    'vat_amount' => $lineVat,
                    'total_price_ht' => $lineHt,
                    'total_price' => $lineTtc,
                ];
            }

            // ── 2. Créer la vente avec totaux pré-calculés ──
            $sale = DB::transaction(function () use (
                $companyId, $session, $warehouse, $company,
                $paymentMethod, $paymentDetails, $customerId, $discountPercent,
                $itemsToCreate, $totalHt, $totalVat, $totalTtc
            ) {
                // Client comptoir par défaut
                $resolvedCustomerId = $customerId;
                if (!$resolvedCustomerId) {
                    $walkIn = Customer::firstOrCreate(
                        ['email' => 'walkin@example.com', 'company_id' => $companyId],
                        [
                            'name' => 'Client comptoir',
                            'company_id' => $companyId,
                            'notes' => 'Client généré automatiquement pour ventes comptoir',
                        ]
                    );
                    $resolvedCustomerId = $walkIn->id;
                }

                $saleData = [
                    'company_id' => $companyId,
                    'customer_id' => $resolvedCustomerId,
                    'warehouse_id' => $warehouse?->id,
                    'cash_session_id' => $session->id,
                    'payment_method' => $paymentMethod,
                    'payment_details' => $paymentDetails,
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'discount_percent' => $discountPercent,
                    'tax_percent' => 0,
                    'total_ht' => $totalHt,
                    'total_vat' => $totalVat,
                    'total' => $totalTtc,
                ];

                if ($company?->emcef_enabled) {
                    $saleData['emcef_status'] = 'pending';
                }

                $sale = Sale::create($saleData);

                // Créer les items et gérer le déstockage FIFO
                foreach ($itemsToCreate as $itemData) {
                    $itemData['sale_id'] = $sale->id;
                    $saleItem = new SaleItem($itemData);
                    $saleItem->save();

                    if ($warehouse) {
                        $warehouse->deductStockFIFO(
                            $itemData['product_id'],
                            $itemData['quantity'],
                            'sale',
                            "Vente POS " . $sale->invoice_number
                        );
                    }
                }

                // Forcer les totaux corrects (au cas où les observers les écrasent)
                DB::table('sales')->where('id', $sale->id)->update([
                    'total_ht' => $totalHt,
                    'total_vat' => $totalVat,
                    'total' => $totalTtc,
                ]);

                // Recalculer la session
                $session->recalculate();

                return $sale;
            });

            // ── 3. Certification e-MCeF (hors transaction) ──
            $emcefResult = null;
            if ($company?->emcef_enabled && $sale) {
                try {
                    $emcefService = new EmcefService($company);
                    $emcefResult = $emcefService->submitInvoice($sale);
                    $sale->refresh();
                } catch (\Exception $e) {
                    \Log::error('POS e-MCeF Error', [
                        'sale_id' => $sale->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $sale->refresh();
            $session->refresh();

            return [
                'success' => true,
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => $sale->total,
                'emcef' => $emcefResult ? [
                    'success' => $emcefResult['success'] ?? false,
                    'nim' => $sale->emcef_nim,
                    'code' => $sale->emcef_code_mecef,
                    'error' => $emcefResult['error'] ?? null,
                ] : null,
                'session' => $this->formatSessionStats($session),
            ];

        } catch (\Throwable $e) {
            \Log::error('PosService recordSale Error: ' . $e->getMessage(), [
                'company_id' => $companyId,
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────
    //  SALE CANCELLATION
    // ──────────────────────────────────────────────

    /**
     * Annule une vente et restaure le stock.
     */
    public function cancelSale(int $companyId, int $saleId): array
    {
        $sale = Sale::where('id', $saleId)
            ->where('company_id', $companyId)
            ->with('items')
            ->first();

        if (!$sale) {
            return ['success' => false, 'message' => 'Vente introuvable'];
        }

        if ($sale->status === 'cancelled') {
            return ['success' => false, 'message' => 'Vente déjà annulée'];
        }

        DB::transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }
            $sale->update(['status' => 'cancelled']);
        });

        if ($sale->cash_session_id) {
            CashSession::find($sale->cash_session_id)?->recalculate();
        }

        return ['success' => true];
    }

    // ──────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────

    /**
     * Formate les statistiques de session pour les réponses API.
     */
    public function formatSessionStats(CashSession $session): array
    {
        return [
            'id' => $session->id,
            'opening_amount' => floatval($session->opening_amount),
            'total_sales' => floatval($session->total_sales),
            'sales_count' => intval($session->sales_count ?? 0),
            'cash_sales' => floatval($session->total_cash),
            'card_sales' => floatval($session->total_card),
            'mobile_sales' => floatval($session->total_mobile),
            'cash_in_drawer' => floatval($session->opening_amount) + floatval($session->total_cash),
            'opened_at' => $session->opened_at?->format('H:i'),
        ];
    }
}
