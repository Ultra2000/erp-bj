<?php

namespace App\Filament\Pages\Cashier;

use Filament\Pages\Page;
use App\Models\Sale;
use App\Services\PosService;
use Filament\Facades\Filament;
use Livewire\Attributes\On;

/**
 * Page Filament pour le Point de Vente.
 * 
 * Rôle : enregistrement navigation Filament + délégation à PosService.
 * Aucune logique métier dupliquée.
 */
class CashRegisterPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'filament.pages.cashier.cash-register';
    protected static ?string $navigationLabel = 'Caisse';
    protected static ?string $title = 'Point de vente';
    protected static ?string $navigationGroup = 'Point de Vente';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('pos')) {
            return false;
        }
        
        $user = auth()->user();
        if (!$user) return false;
        
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission('sales.create') || $user->hasPermission('sales.*');
        }
        
        return in_array($user->role ?? '', ['cashier', 'admin', 'manager']);
    }

    protected function getPosService(): PosService
    {
        return app(PosService::class);
    }

    protected function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    // ── Session ─────────────────────────────────

    public function getOpenSession(): ?\App\Models\CashSession
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return null;

        return $this->getPosService()->getOpenSession($companyId, auth()->id());
    }

    public function openSession(float $openingAmount): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return ['success' => false, 'message' => 'Aucune entreprise sélectionnée'];
        }

        return $this->getPosService()->openSession($companyId, auth()->id(), $openingAmount);
    }

    public function closeSession(float $closingAmount, ?string $notes = null): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return ['success' => false, 'message' => 'Aucune entreprise sélectionnée'];
        }

        return $this->getPosService()->closeSession($companyId, auth()->id(), $closingAmount, $notes);
    }

    public function hasOpenSession(): bool
    {
        return $this->getOpenSession() !== null;
    }

    public function getSessionStats(): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return [];

        $result = $this->getPosService()->checkSession($companyId, auth()->id());
        return $result['session'] ?? [];
    }

    // ── Products ────────────────────────────────

    public function searchProducts(string $query): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId || strlen($query) < 1) return [];

        $warehouse = $this->getPosService()->resolveWarehouse($companyId, auth()->id());

        return $this->getPosService()->searchProducts($companyId, $query, $warehouse);
    }

    public function getProductByBarcode(string $code): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return ['success' => false, 'message' => 'Aucune entreprise'];
        }

        $warehouse = $this->getPosService()->resolveWarehouse($companyId, auth()->id());
        $product = $this->getPosService()->getProductByBarcode($companyId, $code, $warehouse);

        if (!$product) {
            return ['success' => false, 'message' => 'Produit introuvable'];
        }

        return ['success' => true, 'data' => $product];
    }

    // ── Sales ───────────────────────────────────

    public function getTodaySales(): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return [];

        return Sale::with(['items.product', 'customer'])
            ->where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function cancelSale(int $saleId): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return ['success' => false, 'message' => 'Aucune entreprise sélectionnée'];
        }

        return $this->getPosService()->cancelSale($companyId, $saleId);
    }

    public function recordSale(array $payload): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return ['success' => false, 'message' => 'Aucune entreprise sélectionnée'];
        }

        $session = $this->getOpenSession();
        if (!$session) {
            return ['success' => false, 'message' => 'Veuillez ouvrir une session de caisse'];
        }

        $warehouse = $this->getPosService()->resolveWarehouse($companyId, auth()->id());

        return $this->getPosService()->recordSale(
            companyId: $companyId,
            session: $session,
            items: $payload['items'] ?? [],
            paymentMethod: $payload['payment_method'] ?? 'cash',
            paymentDetails: $payload['payment_details'] ?? null,
            customerId: $payload['customer_id'] ?? null,
            discountPercent: floatval($payload['discount_percent'] ?? 0),
            warehouse: $warehouse
        );
    }
}
