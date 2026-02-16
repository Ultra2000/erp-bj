<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

class CaisseController extends Controller
{
    protected function getCompanyId(Request $request): ?int
    {
        // Essayer de récupérer le tenant de Filament
        $tenant = Filament::getTenant();
        if ($tenant) {
            return $tenant->id;
        }
        
        // Sinon, prendre le company_id de la session ou du header
        if ($request->hasHeader('X-Company-Id')) {
            return (int) $request->header('X-Company-Id');
        }
        
        // Ou de la session
        if (session()->has('filament_tenant_id')) {
            return (int) session('filament_tenant_id');
        }
        
        // Récupérer la première company de l'utilisateur
        $user = auth()->user();
        if ($user && method_exists($user, 'companies')) {
            $company = $user->companies()->first();
            return $company?->id;
        }
        
        // Fallback: récupérer via la relation directe si l'user a company_id
        if ($user && isset($user->company_id)) {
            return $user->company_id;
        }
        
        return null;
    }

    /**
     * Récupère l'entrepôt de l'utilisateur pour le POS
     */
    protected function getUserWarehouse(Request $request): ?Warehouse
    {
        $companyId = $this->getCompanyId($request);
        $user = auth()->user();
        
        if (!$companyId || !$user) {
            \Log::warning('getUserWarehouse: No company or user', ['companyId' => $companyId, 'user' => $user?->email]);
            return null;
        }

        // 1. Si l'utilisateur a un entrepôt assigné, l'utiliser
        $userWarehouse = $user->warehouses()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByPivot('is_default', 'desc')
            ->first();

        if ($userWarehouse) {
            \Log::info('getUserWarehouse: Found user warehouse', ['warehouse' => $userWarehouse->name]);
            return $userWarehouse;
        }

        // 2. Chercher l'entrepôt POS de l'entreprise
        $posWarehouse = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_pos_location', true)
            ->first();
            
        if ($posWarehouse) {
            \Log::info('getUserWarehouse: Found POS warehouse', ['warehouse' => $posWarehouse->name]);
            return $posWarehouse;
        }

        // 3. Chercher l'entrepôt par défaut de l'entreprise
        $defaultWarehouse = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
            
        if ($defaultWarehouse) {
            \Log::info('getUserWarehouse: Found default warehouse', ['warehouse' => $defaultWarehouse->name]);
            return $defaultWarehouse;
        }

        // 4. Prendre n'importe quel entrepôt actif de l'entreprise
        $anyWarehouse = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();
            
        if ($anyWarehouse) {
            \Log::info('getUserWarehouse: Found any warehouse', ['warehouse' => $anyWarehouse->name]);
            return $anyWarehouse;
        }

        \Log::warning('getUserWarehouse: No warehouse found for company', ['companyId' => $companyId]);
        return null;
    }

    protected function getOpenSession(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) return null;

        return CashSession::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
    }

    // Vérifier si une session est ouverte
    public function checkSession(Request $request)
    {
        $session = $this->getOpenSession($request);
        
        if (!$session) {
            return response()->json([
                'open' => false,
                'session' => null
            ]);
        }

        $session->recalculate();

        return response()->json([
            'open' => true,
            'session' => [
                'id' => $session->id,
                'opening_amount' => $session->opening_amount,
                'total_sales' => $session->total_sales,
                'sales_count' => $session->sales_count,
                'cash_sales' => $session->total_cash,
                'card_sales' => $session->total_card,
                'mobile_sales' => $session->total_mobile,
                'cash_in_drawer' => $session->opening_amount + $session->total_cash,
                'opened_at' => $session->opened_at->format('H:i'),
            ]
        ]);
    }

    // Ouvrir une session
    public function openSession(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune entreprise sélectionnée'
            ], 400);
        }

        // Vérifier s'il y a déjà une session ouverte
        $existingSession = $this->getOpenSession($request);
        if ($existingSession) {
            return response()->json([
                'success' => false,
                'message' => 'Une session est déjà ouverte'
            ], 400);
        }

        $openingAmount = floatval($request->input('opening_amount', 0));
        
        $session = CashSession::create([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'opening_amount' => $openingAmount,
            'opened_at' => now(),
            'total_sales' => 0,
            'total_cash' => 0,
            'total_card' => 0,
            'total_mobile' => 0,
            'sales_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'opening_amount' => $session->opening_amount,
                'total_sales' => 0,
                'sales_count' => 0,
                'cash_sales' => 0,
                'card_sales' => 0,
                'mobile_sales' => 0,
                'cash_in_drawer' => $session->opening_amount,
                'opened_at' => $session->opened_at->format('H:i'),
            ]
        ]);
    }

    // Fermer une session
    public function closeSession(Request $request)
    {
        $session = $this->getOpenSession($request);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune session ouverte'
            ], 400);
        }

        $closingAmount = floatval($request->input('closing_amount', 0));
        $expectedCash = $session->opening_amount + $session->total_cash;
        
        $session->update([
            'closing_amount' => $closingAmount,
            'closed_at' => now(),
            'difference' => $closingAmount - $expectedCash,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'session' => $session,
            'difference' => $closingAmount - $expectedCash,
        ]);
    }

    // Liste des produits
    public function products(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json([]);
        }

        $warehouse = $this->getUserWarehouse($request);
        
        // Si l'utilisateur a un entrepôt assigné, filtrer les produits par stock de cet entrepôt
        if ($warehouse) {
            $products = $warehouse->products()
                ->where('product_warehouse.quantity', '>', 0)
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'selling_price' => $p->price,
                        'quantity' => $p->pivot->quantity, // Stock de l'entrepôt
                    ];
                });
        } else {
            // Fallback: stock global
            $products = Product::where('company_id', $companyId)
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name', 'code', 'price', 'stock'])
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'selling_price' => $p->price,
                        'quantity' => $p->stock,
                    ];
                });
        }

        return response()->json($products);
    }

    // Recherche de produits
    public function searchProducts(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $query = $request->query('q', '');

        if (!$companyId || strlen($query) < 1) {
            return response()->json([]);
        }

        $warehouse = $this->getUserWarehouse($request);
        
        if ($warehouse) {
            // Recherche dans le stock de l'entrepôt
            $products = $warehouse->products()
                ->where(function ($q) use ($query) {
                    $q->where('products.name', 'like', "%{$query}%")
                      ->orWhere('products.code', 'like', "%{$query}%");
                })
                ->where('product_warehouse.quantity', '>', 0)
                ->orderBy('products.name')
                ->limit(25)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'selling_price' => $p->price,
                        'quantity' => $p->pivot->quantity,
                    ];
                });
        } else {
            // Fallback: recherche globale
            $products = Product::where('company_id', $companyId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%");
                })
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->limit(25)
                ->get(['id', 'name', 'code', 'price', 'stock'])
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'selling_price' => $p->price,
                        'quantity' => $p->stock,
                    ];
                });
        }

        return response()->json($products);
    }

    // Produit par code-barres
    public function productByBarcode(Request $request, string $code)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'No company'], 400);
        }

        $warehouse = $this->getUserWarehouse($request);
        
        if ($warehouse) {
            // Chercher dans le stock de l'entrepôt
            $product = $warehouse->products()
                ->where('products.code', $code)
                ->first();

            if (!$product) {
                return response()->json(['error' => 'Product not found in warehouse'], 404);
            }

            return response()->json([
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'selling_price' => $product->price,
                'quantity' => $product->pivot->quantity,
            ]);
        } else {
            // Fallback: recherche globale
            $product = Product::where('company_id', $companyId)
                ->where('code', $code)
                ->first(['id', 'name', 'code', 'price', 'stock']);

            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            return response()->json([
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'selling_price' => $product->price,
                'quantity' => $product->stock,
            ]);
        }
    }

    // Enregistrer une vente
    public function recordSale(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune entreprise sélectionnée'
            ], 400);
        }

        $session = $this->getOpenSession($request);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez ouvrir une session de caisse'
            ], 400);
        }

        $items = $request->input('items', []);
        $paymentMethod = $request->input('payment_method', 'cash');

        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Le panier est vide'
            ], 400);
        }

        $company = Company::find($companyId);
        $warehouse = $this->getUserWarehouse($request);
        
        \Log::info('POS Sale Start', [
            'company_id' => $companyId,
            'warehouse' => $warehouse ? ['id' => $warehouse->id, 'name' => $warehouse->name] : null,
            'user' => auth()->user()?->email,
            'items_count' => count($items)
        ]);

        try {
            // 1. PRÉ-CALCULER les totaux AVANT toute opération
            $itemsToCreate = [];
            $totalHt = 0;
            $totalVat = 0;
            $totalTtc = 0;
            
            foreach ($items as $item) {
                $product = Product::where('company_id', $companyId)->findOrFail($item['product_id']);
                $qty = floatval($item['quantity']);
                // Toujours utiliser sale_price_ht (garanti HT) pour éviter la double taxation
                $price = floatval($product->sale_price_ht ?? $product->price);
                $vatRate = $product->vat_rate_sale ?? 18;
                
                // Vérifier stock
                if ($warehouse) {
                    $stock = DB::table('product_warehouse')
                        ->where('product_id', $product->id)
                        ->where('warehouse_id', $warehouse->id)
                        ->sum('quantity') ?? 0;
                        
                    if ($stock < $qty) {
                        throw new \RuntimeException("Stock insuffisant pour {$product->name}");
                    }
                }
                
                // Calculs: TVA + taxe spécifique cumulatives
                $lineHt = $qty * $price;
                $lineVat = round($lineHt * ($vatRate / 100), 2);
                $lineTaxSpec = ($product->tax_specific_amount > 0) ? round($product->tax_specific_amount * $qty, 2) : 0;
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
            
            // 2. CRÉER la vente avec totaux pré-calculés
            $sale = DB::transaction(function () use ($companyId, $session, $warehouse, $company, $paymentMethod, $itemsToCreate, $totalHt, $totalVat, $totalTtc) {
                
                // Client par défaut
                $walkIn = Customer::firstOrCreate(
                    ['email' => 'walkin@example.com', 'company_id' => $companyId],
                    [
                        'name' => 'Client comptoir',
                        'company_id' => $companyId,
                        'notes' => 'Client généré automatiquement pour ventes comptoir',
                    ]
                );

                // Créer vente avec totaux définis
                $saleData = [
                    'company_id' => $companyId,
                    'customer_id' => $walkIn->id,
                    'warehouse_id' => $warehouse?->id,
                    'cash_session_id' => $session->id,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'discount_percent' => 0,
                    'tax_percent' => 0,
                    'total_ht' => $totalHt,
                    'total_vat' => $totalVat,
                    'total' => $totalTtc,
                ];
                
                if ($company?->emcef_enabled) {
                    $saleData['emcef_status'] = 'pending';
                }
                
                $sale = Sale::create($saleData);
                
                // Créer les items et gérer le déstockage manuellement
                foreach ($itemsToCreate as $itemData) {
                    $itemData['sale_id'] = $sale->id;
                    $saleItem = new SaleItem($itemData);
                    $saleItem->save(); // Force les observers à se déclencher
                    
                    // Déstockage manuel pour assurer la cohérence
                    if ($warehouse) {
                        \Log::info('POS Stock Deduction', [
                            'warehouse_id' => $warehouse->id,
                            'warehouse_name' => $warehouse->name,
                            'product_id' => $itemData['product_id'],
                            'quantity' => $itemData['quantity'],
                            'sale' => $sale->invoice_number
                        ]);
                        
                        $warehouse->deductStockFIFO(
                            $itemData['product_id'],
                            $itemData['quantity'],
                            'sale',
                            "Vente POS " . $sale->invoice_number
                        );
                    } else {
                        \Log::warning('POS No Warehouse', [
                            'sale' => $sale->invoice_number,
                            'product_id' => $itemData['product_id'],
                        ]);
                    }
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
            $emcefResult = null;
            if ($company?->emcef_enabled && $sale) {
                try {
                    $emcefService = new \App\Services\EmcefService($company);
                    $emcefResult = $emcefService->submitInvoice($sale);
                    $sale->refresh();
                } catch (\Exception $e) {
                    \Log::error('POS e-MCeF Error', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
                }
            }

            $sale->refresh();

            return response()->json([
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
                'session' => [
                    'id' => $session->id,
                    'opening_amount' => $session->opening_amount,
                    'total_sales' => $session->total_sales,
                    'sales_count' => $session->sales_count,
                    'cash_sales' => $session->total_cash,
                    'card_sales' => $session->total_card,
                    'mobile_sales' => $session->total_mobile,
                    'cash_in_drawer' => $session->opening_amount + $session->total_cash,
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('CaisseController Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
