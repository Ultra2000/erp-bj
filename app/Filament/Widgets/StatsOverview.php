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
use Livewire\Attributes\On;

class StatsOverview extends BaseWidget
{
    public ?int $selectedWarehouse = null;

    #[On('warehouse-filter-changed')]
    public function updateWarehouseFilter(?int $warehouseId): void
    {
        $this->selectedWarehouse = $warehouseId;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $currency = Filament::getTenant()->currency ?? 'FCFA';
        $warehouseIds = null;
        $warehouseLabel = 'Total';
        
        // Déterminer les entrepôts à filtrer
        if ($user && $user->hasWarehouseRestriction()) {
            // Utilisateur restreint: ses entrepôts assignés
            $warehouseIds = $user->accessibleWarehouseIds();
            $warehouse = $user->defaultWarehouse();
            $warehouseLabel = $warehouse ? $warehouse->name : 'Mon entrepôt';
        } elseif ($this->selectedWarehouse) {
            // Admin avec filtre actif
            $warehouseIds = [$this->selectedWarehouse];
            $warehouse = \App\Models\Warehouse::find($this->selectedWarehouse);
            $warehouseLabel = $warehouse ? $warehouse->name : 'Entrepôt sélectionné';
        }
        
        // Calculer les stats selon le filtre
        if ($warehouseIds) {
            $totalSales = Sale::where('status', 'completed')
                ->whereIn('warehouse_id', $warehouseIds)
                ->sum('total');
            
            $totalProducts = Inventory::whereIn('warehouse_id', $warehouseIds)
                ->where('quantity', '>', 0)
                ->distinct('product_id')
                ->count('product_id');
            
            $lowStockProducts = Inventory::whereIn('warehouse_id', $warehouseIds)
                ->whereColumn('quantity', '<=', 'min_quantity')
                ->count();
            
            $totalCustomers = Customer::whereHas('sales', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            })->count();
        } else {
            // Tous les entrepôts
            $totalSales = Sale::where('status', 'completed')->sum('total');
            $totalProducts = Product::count();
            $lowStockProducts = Product::where('stock', '<', 10)->count();
            $totalCustomers = Customer::count();
        }

        return [
            Stat::make('Chiffre d\'affaires', number_format($totalSales, 0, ',', ' ') . ' ' . $currency)
                ->description($warehouseLabel . ' - Ventes terminées')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Produits en stock', $totalProducts)
                ->description($warehouseLabel)
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
            Stat::make('Produits en alerte', $lowStockProducts)
                ->description($warehouseLabel . ' - Stock faible')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
            Stat::make('Clients', $totalCustomers)
                ->description($warehouseLabel)
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
} 