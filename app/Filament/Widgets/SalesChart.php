<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Ventes des 7 derniers jours';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = Auth::user();
        
        $query = Sale::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7));
        
        // Filtrer par entrepôts de l'utilisateur si restriction
        if ($user && $user->hasWarehouseRestriction()) {
            $query->whereIn('warehouse_id', $user->accessibleWarehouseIds());
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