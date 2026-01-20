<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Inventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $currency = Filament::getTenant()->currency ?? 'FCFA';
        
        // Filtrer par entrepôts de l'utilisateur si restriction
        if ($user && $user->hasWarehouseRestriction()) {
            $warehouseIds = $user->accessibleWarehouseIds();
            
            $totalSales = Sale::where('status', 'completed')
                ->whereIn('warehouse_id', $warehouseIds)
                ->sum('total');
            
            // Stock des produits dans les entrepôts accessibles
            $totalProducts = Inventory::whereIn('warehouse_id', $warehouseIds)
                ->where('quantity', '>', 0)
                ->distinct('product_id')
                ->count('product_id');
            
            // Produits en alerte dans les entrepôts accessibles
            $lowStockProducts = Inventory::whereIn('warehouse_id', $warehouseIds)
                ->whereColumn('quantity', '<=', 'min_quantity')
                ->count();
            
            // Clients liés aux ventes de ces entrepôts
            $totalCustomers = Customer::whereHas('sales', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            })->count();
        } else {
            $totalSales = Sale::where('status', 'completed')->sum('total');
            $totalProducts = Product::count();
            $lowStockProducts = Product::where('stock', '<', 10)->count();
            $totalCustomers = Customer::count();
        }

        return [
            Stat::make('Chiffre d\'affaires', number_format($totalSales, 0, ',', ' ') . ' ' . $currency)
                ->description('Total des ventes terminées')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Produits en stock', $totalProducts)
                ->description('Nombre total de produits')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make('Produits en alerte', $lowStockProducts)
                ->description('Stock inférieur à 10 unités')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
            Stat::make('Clients', $totalCustomers)
                ->description('Nombre total de clients')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
} 