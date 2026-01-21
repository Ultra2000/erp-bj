<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\Warehouse;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class WarehouseComparison extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Comparatif Boutiques';
    protected static ?string $title = 'Comparatif des Ventes par Boutique';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.warehouse-comparison';

    public ?int $selectedYear = null;
    public ?int $selectedMonth = null;

    public function mount(): void
    {
        $this->selectedYear = (int) now()->year;
        $this->selectedMonth = (int) now()->month;
    }

    /**
     * Admin only
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('super_admin') || $user->hasRole('admin'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    /**
     * Récupère les statistiques de toutes les boutiques
     */
    public function getWarehouseStats(): array
    {
        $companyId = $this->getCompanyId();
        
        // Période actuelle
        $currentStart = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $currentEnd = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();
        
        // Période précédente
        $previousStart = $currentStart->copy()->subMonth()->startOfMonth();
        $previousEnd = $currentStart->copy()->subMonth()->endOfMonth();

        // Récupérer toutes les boutiques de l'entreprise
        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $stats = [];
        $totalCurrentCA = 0;

        foreach ($warehouses as $warehouse) {
            // Ventes du mois en cours
            $currentSales = Sale::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('warehouse_id', $warehouse->id)
                ->where('status', 'completed')
                ->where('type', '!=', 'credit_note')
                ->whereBetween('created_at', [$currentStart, $currentEnd]);

            $currentCount = (clone $currentSales)->count();
            $currentCA = (clone $currentSales)->sum('total');
            $currentHT = (clone $currentSales)->sum('total_ht');
            $currentVAT = (clone $currentSales)->sum('total_vat');

            // Ventes du mois précédent
            $previousSales = Sale::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('warehouse_id', $warehouse->id)
                ->where('status', 'completed')
                ->where('type', '!=', 'credit_note')
                ->whereBetween('created_at', [$previousStart, $previousEnd]);

            $previousCA = (clone $previousSales)->sum('total');
            $previousCount = (clone $previousSales)->count();

            // Calcul de l'évolution
            $evolution = $previousCA > 0 
                ? round((($currentCA - $previousCA) / $previousCA) * 100, 1)
                : ($currentCA > 0 ? 100 : 0);

            // Panier moyen
            $avgBasket = $currentCount > 0 ? $currentCA / $currentCount : 0;

            $totalCurrentCA += $currentCA;

            $stats[] = [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'current_count' => $currentCount,
                'current_ca' => $currentCA,
                'current_ht' => $currentHT,
                'current_vat' => $currentVAT,
                'previous_ca' => $previousCA,
                'previous_count' => $previousCount,
                'evolution' => $evolution,
                'avg_basket' => $avgBasket,
            ];
        }

        // Trier par CA décroissant
        usort($stats, fn($a, $b) => $b['current_ca'] <=> $a['current_ca']);

        // Ajouter le pourcentage du total et le rang
        foreach ($stats as $index => &$stat) {
            $stat['rank'] = $index + 1;
            $stat['percentage'] = $totalCurrentCA > 0 
                ? round(($stat['current_ca'] / $totalCurrentCA) * 100, 1) 
                : 0;
        }

        return [
            'warehouses' => $stats,
            'total_ca' => $totalCurrentCA,
            'period' => $this->getMonthName($this->selectedMonth) . ' ' . $this->selectedYear,
            'previous_period' => $this->getMonthName($previousStart->month) . ' ' . $previousStart->year,
        ];
    }

    /**
     * Données pour le graphique d'évolution mensuelle (6 derniers mois)
     */
    public function getMonthlyEvolution(): array
    {
        $companyId = $this->getCompanyId();
        
        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $months = [];
        $data = [];

        // Générer les 6 derniers mois
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->subMonths($i);
            $months[] = $this->getShortMonthName($date->month) . ' ' . substr($date->year, -2);
        }

        // Couleurs pour les boutiques
        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

        foreach ($warehouses as $index => $warehouse) {
            $monthlyData = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->subMonths($i);
                
                $ca = Sale::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('status', 'completed')
                    ->where('type', '!=', 'credit_note')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total');
                
                $monthlyData[] = round($ca);
            }

            $data[] = [
                'name' => $warehouse->name,
                'data' => $monthlyData,
                'color' => $colors[$index % count($colors)],
            ];
        }

        return [
            'labels' => $months,
            'datasets' => $data,
        ];
    }

    /**
     * Table des détails
     */
    public function table(Table $table): Table
    {
        $stats = $this->getWarehouseStats();
        
        return $table
            ->query(
                Warehouse::where('company_id', $this->getCompanyId())
                    ->where('is_active', true)
            )
            ->columns([
                TextColumn::make('rank')
                    ->label('#')
                    ->state(function ($record) use ($stats) {
                        $found = collect($stats['warehouses'])->firstWhere('id', $record->id);
                        return $found ? $found['rank'] : '-';
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        1 => 'warning',
                        2 => 'gray',
                        3 => 'danger',
                        default => 'primary',
                    }),
                TextColumn::make('name')
                    ->label('Boutique')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('sales_count')
                    ->label('Ventes')
                    ->state(function ($record) use ($stats) {
                        $found = collect($stats['warehouses'])->firstWhere('id', $record->id);
                        return $found ? $found['current_count'] : 0;
                    })
                    ->alignCenter(),
                TextColumn::make('ca_ht')
                    ->label('CA HT')
                    ->state(function ($record) use ($stats) {
                        $found = collect($stats['warehouses'])->firstWhere('id', $record->id);
                        return $found ? number_format($found['current_ht'], 0, ',', ' ') . ' FCFA' : '-';
                    })
                    ->alignEnd(),
                TextColumn::make('ca_ttc')
                    ->label('CA TTC')
                    ->state(function ($record) use ($stats) {
                        $found = collect($stats['warehouses'])->firstWhere('id', $record->id);
                        return $found ? number_format($found['current_ca'], 0, ',', ' ') . ' FCFA' : '-';
                    })
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('avg_basket')
                    ->label('Panier moyen')
                    ->state(function ($record) use ($stats) {
                        $found = collect($stats['warehouses'])->firstWhere('id', $record->id);
                        return $found ? number_format($found['avg_basket'], 0, ',', ' ') . ' FCFA' : '-';
                    })
                    ->alignEnd(),
                TextColumn::make('evolution')
                    ->label('Évolution')
                    ->state(function ($record) use ($stats) {
                        $found = collect($stats['warehouses'])->firstWhere('id', $record->id);
                        if (!$found) return '-';
                        $evo = $found['evolution'];
                        return ($evo >= 0 ? '+' : '') . $evo . '%';
                    })
                    ->badge()
                    ->color(function ($record) use ($stats) {
                        $found = collect($stats['warehouses'])->firstWhere('id', $record->id);
                        if (!$found) return 'gray';
                        return $found['evolution'] >= 0 ? 'success' : 'danger';
                    })
                    ->alignCenter(),
                TextColumn::make('percentage')
                    ->label('Part')
                    ->state(function ($record) use ($stats) {
                        $found = collect($stats['warehouses'])->firstWhere('id', $record->id);
                        return $found ? $found['percentage'] . '%' : '-';
                    })
                    ->alignCenter(),
            ])
            ->defaultSort('name')
            ->striped()
            ->paginated(false);
    }

    protected function getMonthName(int $month): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        return $months[$month] ?? '';
    }

    protected function getShortMonthName(int $month): string
    {
        $months = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
        ];
        return $months[$month] ?? '';
    }
}
