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
            return null;
        }

        // Si l'utilisateur a un entrepôt par défaut, l'utiliser
        $userWarehouse = $user->warehouses()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByPivot('is_default', 'desc')
            ->first();

        if ($userWarehouse) {
            return $userWarehouse;
        }

        // Si l'utilisateur est admin, prendre l'entrepôt POS par défaut de l'entreprise
        if ($user->is_super_admin || $user->isAdmin()) {
            return Warehouse::where('company_id', $companyId)
                ->where('is_active', true)
                ->where(function($q) {
                    $q->where('is_pos_location', true)
                      ->orWhere('is_default', true);
                })
                ->first();
        }

        return null;
    }

    protected function getOpenSession(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) return null;

        return CashSession::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->whereNull('closed_at')
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
        $total = floatval($request->input('total', 0));

        if (empty($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Le panier est vide'
            ], 400);
        }

        // Récupérer l'entreprise et l'entrepôt de l'utilisateur
        $company = Company::find($companyId);
        $warehouse = $this->getUserWarehouse($request);
        $defaultVatRate = $company?->emcef_enabled ? 18 : 20;
        $defaultVatCategory = $company?->emcef_enabled ? 'A' : 'S';

        try {
            $sale = DB::transaction(function () use ($items, $paymentMethod, $total, $companyId, $session, $company, $warehouse, $defaultVatRate, $defaultVatCategory) {
                // Client comptoir par défaut
                $walkIn = Customer::firstOrCreate(
                    ['email' => 'walkin@example.com', 'company_id' => $companyId],
                    [
                        'name' => 'Client comptoir',
                        'company_id' => $companyId,
                        'phone' => null,
                        'address' => null,
                        'city' => null,
                        'country' => null,
                        'notes' => 'Client généré automatiquement pour ventes comptoir',
                    ]
                );

                $sale = new Sale([
                    'company_id' => $companyId,
                    'customer_id' => $walkIn->id,
                    'warehouse_id' => $warehouse?->id, // Entrepôt de la vente
                    'cash_session_id' => $session->id,
                    'payment_method' => $paymentMethod,
                    'status' => 'completed',
                    'discount_percent' => 0,
                    'tax_percent' => 0,
                ]);
                
                // Préparer le statut e-MCeF si activé
                if ($company?->emcef_enabled) {
                    $sale->emcef_status = 'pending';
                }
                
                $sale->save();

                $totalHt = 0;
                $totalVat = 0;

                foreach ($items as $item) {
                    $product = Product::where('company_id', $companyId)
                        ->lockForUpdate()
                        ->findOrFail($item['product_id']);

                    $qty = (int) $item['quantity'];
                    $price = floatval($item['price']);
                    $vatRate = $product->vat_rate_sale ?? $defaultVatRate;
                    $vatCategory = $product->vat_category ?? $defaultVatCategory;

                    // Vérifier et décrémenter le stock de l'entrepôt si défini
                    if ($warehouse) {
                        $warehouseStock = DB::table('product_warehouse')
                            ->where('product_id', $product->id)
                            ->where('warehouse_id', $warehouse->id)
                            ->lockForUpdate()
                            ->first();
                        
                        $availableStock = $warehouseStock ? $warehouseStock->quantity : 0;
                        
                        if ($availableStock < $qty) {
                            throw new \RuntimeException("Stock insuffisant pour {$product->name} dans l'entrepôt {$warehouse->name}");
                        }
                        
                        // Décrémenter le stock de l'entrepôt
                        DB::table('product_warehouse')
                            ->where('product_id', $product->id)
                            ->where('warehouse_id', $warehouse->id)
                            ->decrement('quantity', $qty);
                    } else {
                        // Fallback: stock global
                        if ($product->stock < $qty) {
                            throw new \RuntimeException('Stock insuffisant pour ' . $product->name);
                        }
                        $product->decrement('stock', $qty);
                    }

                    // Calcul HT et TVA
                    $lineHt = $qty * $price;
                    $lineVat = round($lineHt * ($vatRate / 100), 2);
                    $lineTtc = $lineHt + $lineVat;
                    
                    $totalHt += $lineHt;
                    $totalVat += $lineVat;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'unit_price_ht' => $price,
                        'vat_rate' => $vatRate,
                        'vat_category' => $vatCategory,
                        'vat_amount' => $lineVat,
                        'total_price_ht' => $lineHt,
                        'total_price' => $lineTtc,
                    ]);
                }

                // Mettre à jour les totaux de la vente
                $sale->update([
                    'total_ht' => round($totalHt, 2),
                    'total_vat' => round($totalVat, 2),
                    'total' => round($totalHt + $totalVat, 2),
                ]);

                // Mettre à jour la session
                $session->recalculate();

                return $sale;
            });

            $session->refresh();

            // Certification e-MCeF automatique si activé
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

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
