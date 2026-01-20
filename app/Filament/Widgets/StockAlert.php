<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Inventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();
        $warehouseIds = null;
        
        // Déterminer les entrepôts à filtrer
        if ($user && $user->hasWarehouseRestriction()) {
            $warehouseIds = $user->accessibleWarehouseIds();
        } elseif ($this->selectedWarehouse) {
            $warehouseIds = [$this->selectedWarehouse];
        }
        
        // Filtrer par entrepôts si nécessaire
        if ($warehouseIds) {
            $lowStockInventories = Inventory::with(['product', 'warehouse'])
                ->whereIn('warehouse_id', $warehouseIds)
                ->whereColumn('quantity', '<=', 'min_quantity')
                ->orderBy('quantity')
                ->take(5)
                ->get();
            
            if ($lowStockInventories->isEmpty()) {
                return [
                    Stat::make('Aucune alerte', '✓')
                        ->description('Stock OK dans cet entrepôt')
                        ->color('success'),
                ];
            }
            
            return $lowStockInventories->map(function ($inventory) {
                return Stat::make($inventory->product->name ?? 'Produit', $inventory->quantity . ' unités')
                    ->description('Min: ' . $inventory->min_quantity . ' - ' . ($inventory->warehouse->name ?? 'Entrepôt'))
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger');
            })->toArray();
        }
        
        // Tous les entrepôts
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

        return $lowStockProducts->map(function ($product) {
            return Stat::make($product->name, $product->stock . ' unités')
                ->description('Stock restant')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        })->toArray();
    }
} 