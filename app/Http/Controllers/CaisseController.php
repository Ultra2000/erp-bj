<?php

namespace App\Http\Controllers;

use App\Services\PosService;
use Illuminate\Http\Request;
use Filament\Facades\Filament;

/**
 * Contrôleur REST pour le Point de Vente.
 * 
 * Rôle : extraction des données HTTP uniquement.
 * Toute la logique métier est déléguée à PosService.
 */
class CaisseController extends Controller
{
    public function __construct(
        protected PosService $posService
    ) {}

    /**
     * Résout le company_id depuis le contexte HTTP (tenant, header, session, user).
     */
    protected function getCompanyId(Request $request): ?int
    {
        $tenant = Filament::getTenant();
        if ($tenant) return $tenant->id;

        if ($request->hasHeader('X-Company-Id')) {
            return (int) $request->header('X-Company-Id');
        }

        if (session()->has('filament_tenant_id')) {
            return (int) session('filament_tenant_id');
        }

        $user = auth()->user();
        if ($user && method_exists($user, 'companies')) {
            return $user->companies()->first()?->id;
        }
        if ($user && isset($user->company_id)) {
            return $user->company_id;
        }

        return null;
    }

    // ── Session ─────────────────────────────────

    public function checkSession(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['open' => false, 'session' => null]);
        }

        return response()->json(
            $this->posService->checkSession($companyId, auth()->id())
        );
    }

    public function openSession(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'Aucune entreprise sélectionnée'], 400);
        }

        $result = $this->posService->openSession(
            $companyId,
            auth()->id(),
            floatval($request->input('opening_amount', 0))
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function closeSession(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'Aucune entreprise sélectionnée'], 400);
        }

        $result = $this->posService->closeSession(
            $companyId,
            auth()->id(),
            floatval($request->input('closing_amount', 0)),
            $request->input('notes')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ── Products ────────────────────────────────

    public function products(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) return response()->json([]);

        $warehouse = $this->posService->resolveWarehouse($companyId, auth()->id());

        return response()->json(
            $this->posService->getProducts($companyId, $warehouse)
        );
    }

    public function searchProducts(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $query = $request->query('q', '');

        if (!$companyId || strlen($query) < 1) return response()->json([]);

        $warehouse = $this->posService->resolveWarehouse($companyId, auth()->id());

        return response()->json(
            $this->posService->searchProducts($companyId, $query, $warehouse)
        );
    }

    public function productByBarcode(Request $request, string $code)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) return response()->json(['error' => 'No company'], 400);

        $warehouse = $this->posService->resolveWarehouse($companyId, auth()->id());
        $product = $this->posService->getProductByBarcode($companyId, $code, $warehouse);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    // ── Sale ────────────────────────────────────

    public function recordSale(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'Aucune entreprise sélectionnée'], 400);
        }

        $session = $this->posService->getOpenSession($companyId, auth()->id());
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Veuillez ouvrir une session de caisse'], 400);
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'Le panier est vide'], 400);
        }

        $warehouse = $this->posService->resolveWarehouse($companyId, auth()->id());

        $result = $this->posService->recordSale(
            companyId: $companyId,
            session: $session,
            items: $items,
            paymentMethod: $request->input('payment_method', 'cash'),
            paymentDetails: $request->input('payment_details'),
            customerId: $request->input('customer_id'),
            discountPercent: floatval($request->input('discount_percent', 0)),
            warehouse: $warehouse
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
