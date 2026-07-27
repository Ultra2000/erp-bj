<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class TopProductsWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected static string $view = 'filament.widgets.top-products-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    public ?int $selectedWarehouse = null;

    #[On('warehouse-filter-changed')]
    public function updateWarehouseFilter(?int $warehouseId): void
    {
        $this->selectedWarehouse = $warehouseId;
    }

    protected function warehouseIds(): ?array
    {
        $user = Auth::user();

        if ($user && $user->hasWarehouseRestriction()) {
            return $user->accessibleWarehouseIds();
        }
        if ($this->selectedWarehouse) {
            return [$this->selectedWarehouse];
        }
        return null;
    }

    /**
     * Top produits vendus ce mois (par chiffre d'affaires).
     */
    public function getTopProducts(): array
    {
        $companyId = Filament::getTenant()?->id;
        $warehouseIds = $this->warehouseIds();

        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.company_id', $companyId)
            ->where('sales.status', 'completed')
            ->where(fn ($q) => $q->whereNull('sales.type')->orWhere('sales.type', '!=', 'credit_note'))
            ->whereBetween('sales.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->when($warehouseIds, fn ($q) => $q->whereIn('sales.warehouse_id', $warehouseIds))
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name as name, SUM(sale_items.quantity) as qty, SUM(sale_items.total_price) as revenue')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'qty' => (float) $r->qty,
                'revenue' => (float) $r->revenue,
            ])
            ->toArray();
    }

    public function getCurrencyLabel(): string
    {
        return Filament::getTenant()?->currency_label ?? 'FCFA';
    }
}
