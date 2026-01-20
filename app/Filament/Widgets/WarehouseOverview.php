<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use App\Models\StockTransfer;
use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class WarehouseOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 5;

    public ?int $selectedWarehouse = null;

    #[On('warehouse-filter-changed')]
    public function updateWarehouseFilter(?int $warehouseId): void
    {
        $this->selectedWarehouse = $warehouseId;
    }

    protected function getStats(): array
    {
        $companyId = filament()->getTenant()?->id;
        $user = Auth::user();

        if (!$companyId) {
            return [];
        }

        // Déterminer les entrepôts à filtrer
        $warehouseFilter = null;
        $warehouseLabel = 'Tous entrepôts';
        
        if ($user && $user->hasWarehouseRestriction()) {
            $warehouseFilter = $user->accessibleWarehouseIds();
            $warehouse = $user->defaultWarehouse();
            $warehouseLabel = $warehouse ? $warehouse->name : 'Mon entrepôt';
        } elseif ($this->selectedWarehouse) {
            $warehouseFilter = [$this->selectedWarehouse];
            $warehouse = Warehouse::find($this->selectedWarehouse);
            $warehouseLabel = $warehouse ? $warehouse->name : 'Entrepôt';
        }

        // Total stock value across accessible warehouses
        $stockQuery = DB::table('product_warehouse')
            ->join('warehouses', 'warehouses.id', '=', 'product_warehouse.warehouse_id')
            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
            ->where('warehouses.company_id', $companyId);
        
        if ($warehouseFilter) {
            $stockQuery->whereIn('warehouses.id', $warehouseFilter);
        }
        
        $totalStockValue = $stockQuery->selectRaw('SUM(product_warehouse.quantity * COALESCE(products.purchase_price, 0)) as total')
            ->value('total') ?? 0;

        // Low stock alerts
        $lowStockQuery = DB::table('product_warehouse')
            ->join('warehouses', 'warehouses.id', '=', 'product_warehouse.warehouse_id')
            ->where('warehouses.company_id', $companyId)
            ->whereNotNull('product_warehouse.min_quantity')
            ->whereRaw('product_warehouse.quantity <= product_warehouse.min_quantity');
        
        if ($warehouseFilter) {
            $lowStockQuery->whereIn('warehouses.id', $warehouseFilter);
        }
        
        $lowStockCount = $lowStockQuery->count();

        // Pending transfers (only those involving accessible warehouses)
        $transfersQuery = StockTransfer::where('company_id', $companyId)
            ->whereIn('status', ['pending', 'approved', 'in_transit']);
        
        if ($warehouseFilter) {
            $transfersQuery->where(function($q) use ($warehouseFilter) {
                $q->whereIn('source_warehouse_id', $warehouseFilter)
                  ->orWhereIn('destination_warehouse_id', $warehouseFilter);
            });
        }
        
        $pendingTransfers = $transfersQuery->count();

        // Today's movements
        $movementsQuery = StockMovement::where('company_id', $companyId)
            ->whereDate('created_at', today());
        
        if ($warehouseFilter) {
            $movementsQuery->whereIn('warehouse_id', $warehouseFilter);
        }
        
        $todayMovements = $movementsQuery->count();

        return [
            Stat::make('Valeur totale du stock', number_format($totalStockValue, 0, ',', ' ') . ' ' . \Filament\Facades\Filament::getTenant()->currency)
                ->description($warehouseLabel)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getStockValueChart($companyId)),

            Stat::make('Alertes stock', $lowStockCount)
                ->description($warehouseLabel . ' - Rupture')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Transferts en cours', $pendingTransfers)
                ->description('À traiter')
                ->descriptionIcon('heroicon-m-truck')
                ->color($pendingTransfers > 0 ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.stock-transfers.index', ['tenant' => filament()->getTenant()])),

            Stat::make('Mouvements aujourd\'hui', $todayMovements)
                ->description($warehouseLabel)
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }

    protected function getStockValueChart(int $companyId): array
    {
        // Get stock value by day for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            // Simplified - in production you'd calculate historical values
            $data[] = rand(80, 120);
        }
        return $data;
    }
}
