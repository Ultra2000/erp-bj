<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Inventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StockAlert extends BaseWidget
{
    protected function getHeading(): string
    {
        return 'Produits en alerte de stock';
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        
        // Filtrer par entrepôts de l'utilisateur si restriction
        if ($user && $user->hasWarehouseRestriction()) {
            $warehouseIds = $user->accessibleWarehouseIds();
            
            // Récupérer les produits en alerte dans les entrepôts accessibles
            $lowStockInventories = Inventory::with('product')
                ->whereIn('warehouse_id', $warehouseIds)
                ->whereColumn('quantity', '<=', 'min_quantity')
                ->orderBy('quantity')
                ->take(5)
                ->get();
            
            return $lowStockInventories->map(function ($inventory) {
                return Stat::make($inventory->product->name ?? 'Produit', $inventory->quantity . ' unités')
                    ->description('Min: ' . $inventory->min_quantity . ' - ' . ($inventory->warehouse->name ?? 'Entrepôt'))
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger');
            })->toArray();
        }
        
        $lowStockProducts = Product::where('stock', '<', 10)
            ->orderBy('stock')
            ->take(5)
            ->get();

        return $lowStockProducts->map(function ($product) {
            return Stat::make($product->name, $product->stock . ' unités')
                ->description('Stock restant')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        })->toArray();
    }
} 