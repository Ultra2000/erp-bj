<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class StockAlert extends BaseWidget
{
    protected static bool $isDiscovered = false;

    public ?int $selectedWarehouse = null;

    #[On('warehouse-filter-changed')]
    public function updateWarehouseFilter(?int $warehouseId): void
    {
        $this->selectedWarehouse = $warehouseId;
    }

    protected function getHeading(): string
    {
        return 'Produits en alerte de stock';
    }

    protected function getStats(): array
    {
        // Un simple widget de stock ne doit jamais faire planter le tableau de bord.
        try {
            return $this->buildStats();
        } catch (\Throwable $e) {
            Log::warning('StockAlert widget error: ' . $e->getMessage());

            return [
                Stat::make('Alertes stock', 'Indisponible')
                    ->description('Impossible de charger les alertes de stock')
                    ->color('gray'),
            ];
        }
    }

    protected function buildStats(): array
    {
        $user = Auth::user();
        $companyId = Filament::getTenant()?->id;
        $warehouseIds = null;

        // Déterminer les entrepôts à filtrer
        if ($user && $user->hasWarehouseRestriction()) {
            $warehouseIds = $user->accessibleWarehouseIds();
        } elseif ($this->selectedWarehouse) {
            $warehouseIds = [$this->selectedWarehouse];
        }

        $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2, '.', ''), '0'), '.');

        // Stock bas par entrepôt : table product_warehouse (quantity / min_quantity).
        if ($warehouseIds) {
            $rows = DB::table('product_warehouse as pw')
                ->join('products as p', 'p.id', '=', 'pw.product_id')
                ->leftJoin('warehouses as w', 'w.id', '=', 'pw.warehouse_id')
                ->when($companyId, fn ($q) => $q->where('pw.company_id', $companyId))
                ->whereIn('pw.warehouse_id', $warehouseIds)
                ->whereNotNull('pw.min_quantity')
                ->where('pw.min_quantity', '>', 0)
                ->whereColumn('pw.quantity', '<=', 'pw.min_quantity')
                ->orderBy('pw.quantity')
                ->limit(5)
                ->get(['p.name as product_name', 'pw.quantity', 'pw.min_quantity', 'w.name as warehouse_name']);

            if ($rows->isEmpty()) {
                return [
                    Stat::make('Aucune alerte', '✓')
                        ->description('Stock OK dans cet entrepôt')
                        ->color('success'),
                ];
            }

            return $rows->map(fn ($r) => Stat::make($r->product_name ?? 'Produit', $fmtQty($r->quantity) . ' unités')
                ->description('Min: ' . $fmtQty($r->min_quantity) . ' — ' . ($r->warehouse_name ?? 'Entrepôt'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'))
                ->toArray();
        }

        // Tous les entrepôts : stock global du produit
        $lowStockProducts = Product::where('stock', '<', 10)
            ->orderBy('stock')
            ->take(5)
            ->get();

        if ($lowStockProducts->isEmpty()) {
            return [
                Stat::make('Aucune alerte', '✓')
                    ->description('Tous les stocks sont OK')
                    ->color('success'),
            ];
        }

        return $lowStockProducts->map(fn (Product $product) => Stat::make($product->name, $product->stock . ' unités')
            ->description('Stock restant')
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color('danger'))
            ->toArray();
    }
}
