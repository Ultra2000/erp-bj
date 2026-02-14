<?php

namespace App\Filament\Caisse\Pages;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Models\Company;
use App\Services\EmcefService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PointOfSale extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string $view = 'filament.caisse.pages.point-of-sale';
    protected static ?string $navigationLabel = 'Caisse';
    protected static ?string $title = 'Point de Vente';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * Récupère les produits avec recherche
     */
    public function searchProducts(string $query = '', ?int $categoryId = null): array
    {
        $warehouse = $this->getWarehouse();
        $warehouseId = $warehouse?->id;
        
        $products = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.price',
                'products.sale_price_ht',
                'products.wholesale_price',
                'products.wholesale_price_ht',
                'products.min_wholesale_qty',
                'products.min_stock',
                'products.barcode',
                'products.vat_rate_sale',
            ])
            ->when($warehouseId, function ($q) use ($warehouseId) {
                $q->addSelect(['stock' => function($query) use ($warehouseId) {
                    $query->selectRaw('COALESCE(sum(quantity), 0)')
                        ->from('product_warehouse')
                        ->whereColumn('product_id', 'products.id')
                        ->where('warehouse_id', $warehouseId);
                }]);
                $q->whereExists(function ($subquery) use ($warehouseId) {
                    $subquery->selectRaw('1')
                        ->from('product_warehouse')
                        ->whereColumn('product_id', 'products.id')
                        ->where('warehouse_id', $warehouseId)
                        ->havingRaw('sum(quantity) > 0');
                });
            }, function ($q) {
                $q->where('products.stock', '>', 0)
                  ->addSelect('products.stock');
            })
            ->when($query, fn($q) => $q->where(function($q) use ($query) {
                $q->where('products.name', 'like', "%{$query}%")
                  ->orWhere('products.code', 'like', "%{$query}%")
                  ->orWhere('products.barcode', 'like', "%{$query}%");
            }))
            ->orderBy('products.name')
            ->limit(50)
            ->get();

        return $products->toArray();
    }

    /**
     * Récupère un produit par code-barres
     */
    public function getProductByBarcode(string $barcode): ?array
    {
        $warehouse = $this->getWarehouse();
        $warehouseId = $warehouse?->id;
        
        $query = Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.code',
                'products.price',
                'products.sale_price_ht',
                'products.wholesale_price',
                'products.wholesale_price_ht',
                'products.min_wholesale_qty',
                'products.barcode',
                'products.vat_rate_sale',
            ])
            ->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)
                  ->orWhere('code', $barcode);
            });

        if ($warehouseId) {
            $query->addSelect(['stock' => function($q) use ($warehouseId) {
                $q->selectRaw('COALESCE(sum(quantity), 0)')
                    ->from('product_warehouse')
                    ->whereColumn('product_id', 'products.id')
                    ->where('warehouse_id', $warehouseId);
            }]);
        } else {
            $query->addSelect('products.stock');
        }
        
        $product = $query->first();
        return $product?->toArray();
    }

    /**
     * Récupère les clients
     */
    public function getCustomers(): array
    {
        return Customer::orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'phone', 'email'])
            ->toArray();
    }

    /**
     * Vérifie si une session de caisse est ouverte
     */
    public function hasOpenSession(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return false;
        
        return CashSession::where('company_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();
    }

    /**
     * Récupère les stats de la session ouverte
     */
    public function getOpenSessionStats(): ?array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return null;
        
        $session = CashSession::where('company_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
            
        if (!$session) return null;
        
        $session->refresh();
        
        return [
            'sales_count' => (int) ($session->sales_count ?? 0),
            'total_sales' => (float) ($session->total_sales ?? 0),
        ];
    }

    /**
     * Vérifie le stock en temps réel
     */
    public function verifyCartStock(array $cartItems): array
    {
        $warehouse = $this->getWarehouse();
        
        if (!$warehouse) {
            return ['valid' => false, 'errors' => ['Aucun entrepôt configuré']];
        }

        $errors = [];
        $updatedStocks = [];

        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            $requestedQty = $item['quantity'];
            
            $currentStock = DB::table('product_warehouse')
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $productId)
                ->sum('quantity') ?? 0;
            
            $updatedStocks[$productId] = (int) $currentStock;
            
            if ($currentStock < $requestedQty) {
                $product = Product::find($productId);
                $productName = $product?->name ?? "Produit #{$productId}";
                $errors[] = "{$productName} (dispo: {$currentStock}, demandé: {$requestedQty})";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'updatedStocks' => $updatedStocks,
        ];
    }

    /**
     * Enregistre une vente - VERSION SIMPLIFIÉE
     */
    public function recordSale(array $payload): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return ['success' => false, 'message' => 'Entreprise non trouvée'];
        }

        $session = CashSession::where('company_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
            
        if (!$session) {
            return ['success' => false, 'message' => 'Veuillez ouvrir une session de caisse'];
        }

        $warehouse = $this->getWarehouse();
        if (!$warehouse) {
            return ['success' => false, 'message' => 'Aucun entrepôt configuré'];
        }

        try {
            // 1. PRÉ-CALCULER tous les totaux
            $itemsToCreate = [];
            $totalHt = 0;
            $totalVat = 0;
            $totalTtc = 0;
            
            foreach ($payload['items'] as $line) {
                $product = Product::find($line['product_id']);
                if (!$product) {
                    return ['success' => false, 'message' => "Produit introuvable"];
                }
                
                $qty = max(1, (int) $line['quantity']);
                // Toujours utiliser sale_price_ht (garanti HT) pour éviter la double taxation
                $unitPrice = $product->sale_price_ht ?? $product->price;
                $vatRate = $product->vat_rate_sale ?? 18;
                
                // Vérifier stock
                $stock = DB::table('product_warehouse')
                    ->where('warehouse_id', $warehouse->id)
                    ->where('product_id', $product->id)
                    ->sum('quantity') ?? 0;
                    
                if ($stock < $qty) {
                    return ['success' => false, 'message' => "Stock insuffisant pour {$product->name}"];
                }
                
                // Calculs
                $lineHt = $qty * $unitPrice;
                $lineVat = round($lineHt * ($vatRate / 100), 2);
                $lineTtc = $lineHt + $lineVat;
                
                $totalHt += $lineHt;
                $totalVat += $lineVat;
                $totalTtc += $lineTtc;
                
                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_price_ht' => $unitPrice,
                    'vat_rate' => $vatRate,
                    'vat_category' => $product->vat_category ?? 'S',
                    'vat_amount' => $lineVat,
                    'total_price_ht' => $lineHt,
                    'total_price' => $lineTtc,
                ];
            }
            
            // Appliquer remise
            $discountPercent = floatval($payload['discount_percent'] ?? 0);
            if ($discountPercent > 0) {
                $multiplier = 1 - ($discountPercent / 100);
                $totalHt = round($totalHt * $multiplier, 2);
                $totalVat = round($totalVat * $multiplier, 2);
                $totalTtc = round($totalTtc * $multiplier, 2);
            }
            
            // 2. CRÉER la vente
            $sale = DB::transaction(function () use ($tenant, $session, $warehouse, $payload, $itemsToCreate, $totalHt, $totalVat, $totalTtc, $discountPercent) {
                
                // Client par défaut
                $customer = Customer::firstOrCreate(
                    ['email' => 'walkin@pos.local', 'company_id' => $tenant->id],
                    ['name' => 'Client comptoir', 'company_id' => $tenant->id]
                );
                
                // Créer la vente
                $saleData = [
                    'company_id' => $tenant->id,
                    'customer_id' => $payload['customer_id'] ?? $customer->id,
                    'warehouse_id' => $warehouse->id,
                    'cash_session_id' => $session->id,
                    'payment_method' => $payload['payment_method'] ?? 'cash',
                    'discount_percent' => $discountPercent,
                    'status' => 'completed',
                    'total_ht' => $totalHt,
                    'total_vat' => $totalVat,
                    'total' => $totalTtc,
                ];
                
                $company = Company::find($tenant->id);
                if ($company?->emcef_enabled) {
                    $saleData['emcef_status'] = 'pending';
                }
                
                $sale = Sale::create($saleData);
                
                // Créer les items
                foreach ($itemsToCreate as $itemData) {
                    $itemData['sale_id'] = $sale->id;
                    SaleItem::create($itemData);
                }
                
                // FORCER le total correct (au cas où les observers l'écrasent)
                DB::table('sales')->where('id', $sale->id)->update([
                    'total_ht' => $totalHt,
                    'total_vat' => $totalVat,
                    'total' => $totalTtc,
                ]);
                
                // Mettre à jour session
                $session->recalculate();
                
                return $sale;
            });
            
            // 3. e-MCeF (hors transaction)
            $company = Company::find($tenant->id);
            if ($company?->emcef_enabled && $sale) {
                try {
                    $emcefService = new EmcefService($company);
                    $emcefService->submitInvoice($sale);
                } catch (\Exception $e) {
                    \Log::error('POS e-MCeF Error: ' . $e->getMessage());
                }
            }
            
            $sale->refresh();

            return [
                'success' => true,
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => $sale->total,
                'emcef_nim' => $sale->emcef_nim ?? null,
                'emcef_code' => $sale->emcef_code_mecef ?? null,
            ];

        } catch (\Exception $e) {
            \Log::error('POS Sale Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Récupère les dernières ventes de la session
     */
    public function getRecentSales(int $limit = 10): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return [];
        
        $session = CashSession::where('company_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
            
        if (!$session) return [];

        return Sale::where('cash_session_id', $session->id)
            ->with('items.product')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Helper: Récupère l'entrepôt à utiliser
     */
    private function getWarehouse(): ?Warehouse
    {
        $tenant = Filament::getTenant();
        $user = auth()->user();
        
        if ($user && method_exists($user, 'defaultWarehouse')) {
            $warehouse = $user->defaultWarehouse();
            if ($warehouse) return $warehouse;
        }
        
        if ($tenant) {
            $warehouse = Warehouse::getDefault($tenant->id);
            if ($warehouse) return $warehouse;
            
            return Warehouse::where('company_id', $tenant->id)
                ->where('is_active', true)
                ->first();
        }
        
        return null;
    }
}
