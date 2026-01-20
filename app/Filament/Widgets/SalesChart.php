<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;
use Livewire\Attributes\On;

class SalesChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected static ?string $heading = 'Ventes des 7 derniers jours';
    protected static ?int $sort = 2;

    public ?int $selectedWarehouse = null;

    #[On('warehouse-filter-changed')]
    public function updateWarehouseFilter(?int $warehouseId): void
    {
        $this->selectedWarehouse = $warehouseId;
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $warehouseIds = null;
        
        // Déterminer les entrepôts à filtrer
        if ($user && $user->hasWarehouseRestriction()) {
            $warehouseIds = $user->accessibleWarehouseIds();
        } elseif ($this->selectedWarehouse) {
            $warehouseIds = [$this->selectedWarehouse];
        }
        
        $query = Sale::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7));
        
        if ($warehouseIds) {
            $query->whereIn('warehouse_id', $warehouseIds);
        }
        
        $data = $query->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $currency = Filament::getTenant()->currency ?? 'FCFA';

        return [
            'datasets' => [
                [
                    'label' => "Ventes ($currency)",
                    'data' => $data->pluck('total')->toArray(),
                    'borderColor' => '#10B981',
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
} 