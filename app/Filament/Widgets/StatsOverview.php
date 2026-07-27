<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class StatsOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    public ?int $selectedWarehouse = null;

    protected int | string | array $columnSpan = 'full';

    #[On('warehouse-filter-changed')]
    public function updateWarehouseFilter(?int $warehouseId): void
    {
        $this->selectedWarehouse = $warehouseId;
    }

    /**
     * Entrepôts à prendre en compte (null = tous), + libellé.
     */
    protected function resolveWarehouses(): array
    {
        $user = Auth::user();

        if ($user && $user->hasWarehouseRestriction()) {
            $warehouse = $user->defaultWarehouse();
            return [$user->accessibleWarehouseIds(), $warehouse?->name ?? 'Mon entrepôt'];
        }

        if ($this->selectedWarehouse) {
            $warehouse = \App\Models\Warehouse::find($this->selectedWarehouse);
            return [[$this->selectedWarehouse], $warehouse?->name ?? 'Entrepôt'];
        }

        return [null, 'Tous les entrepôts'];
    }

    /**
     * Requête de base des ventes finalisées (hors avoirs), filtrée par entrepôt.
     */
    protected function salesQuery(?array $warehouseIds): Builder
    {
        return Sale::query()
            ->where('status', 'completed')
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'credit_note'))
            ->when($warehouseIds, fn ($q) => $q->whereIn('warehouse_id', $warehouseIds));
    }

    protected function getStats(): array
    {
        $currency = Filament::getTenant()->currency_label ?? 'FCFA';
        [$warehouseIds, $warehouseLabel] = $this->resolveWarehouses();

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $startOfLastMonth = now()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth = now()->subMonthNoOverflow()->endOfMonth();

        // ── Chiffre d'affaires du mois ──
        $caMonth = (float) $this->salesQuery($warehouseIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total');
        $caLastMonth = (float) $this->salesQuery($warehouseIds)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('total');
        $caTrend = $this->trend($caMonth, $caLastMonth);

        // ── CA du jour + nb ventes ──
        $caToday = (float) $this->salesQuery($warehouseIds)
            ->whereDate('created_at', today())->sum('total');
        $salesToday = (int) $this->salesQuery($warehouseIds)
            ->whereDate('created_at', today())->count();

        // ── Ventes du mois + panier moyen ──
        $salesMonth = (int) $this->salesQuery($warehouseIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $panierMoyen = $salesMonth > 0 ? $caMonth / $salesMonth : 0;

        // ── Créances clients (impayés) ──
        $receivables = (float) $this->salesQuery($warehouseIds)
            ->whereRaw('((total + COALESCE(aib_amount, 0)) - COALESCE(amount_paid, 0)) > 0')
            ->selectRaw('COALESCE(SUM((total + COALESCE(aib_amount, 0)) - COALESCE(amount_paid, 0)), 0) as due')
            ->value('due');

        // ── Produits en alerte de stock ──
        $lowStock = Product::where('stock', '<', 10)->count();

        // ── Clients ──
        $customers = Customer::count();

        // ── Sparkline : CA des 7 derniers jours ──
        $spark = $this->last7DaysRevenue($warehouseIds);

        return [
            Stat::make('CA du mois', number_format($caMonth, 0, ',', ' ') . ' ' . $currency)
                ->description($caTrend['label'] . ' vs mois dernier')
                ->descriptionIcon($caTrend['icon'])
                ->color($caTrend['color'])
                ->chart($spark),

            Stat::make('CA aujourd\'hui', number_format($caToday, 0, ',', ' ') . ' ' . $currency)
                ->description($salesToday . ' vente(s) aujourd\'hui')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make('Ventes du mois', $salesMonth)
                ->description('Panier moyen : ' . number_format($panierMoyen, 0, ',', ' ') . ' ' . $currency)
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('primary'),

            Stat::make('Créances clients', number_format($receivables, 0, ',', ' ') . ' ' . $currency)
                ->description($receivables > 0 ? 'À recouvrer' : 'Tout est soldé')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($receivables > 0 ? 'warning' : 'success'),

            Stat::make('Produits en alerte', $lowStock)
                ->description($warehouseLabel . ' - Stock faible')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'success'),

            Stat::make('Clients', $customers)
                ->description('Base clients')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }

    /**
     * Calcule la variation en % entre deux valeurs, avec icône et couleur.
     */
    protected function trend(float $current, float $previous): array
    {
        if ($previous <= 0) {
            return $current > 0
                ? ['label' => 'Nouveau', 'icon' => 'heroicon-m-arrow-trending-up', 'color' => 'success']
                : ['label' => '—', 'icon' => 'heroicon-m-minus', 'color' => 'gray'];
        }

        $pct = round((($current - $previous) / $previous) * 100);

        if ($pct > 0) {
            return ['label' => '+' . $pct . '%', 'icon' => 'heroicon-m-arrow-trending-up', 'color' => 'success'];
        }
        if ($pct < 0) {
            return ['label' => $pct . '%', 'icon' => 'heroicon-m-arrow-trending-down', 'color' => 'danger'];
        }
        return ['label' => 'Stable', 'icon' => 'heroicon-m-minus', 'color' => 'gray'];
    }

    /**
     * CA jour par jour sur les 7 derniers jours (pour le sparkline).
     */
    protected function last7DaysRevenue(?array $warehouseIds): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $data[] = (float) $this->salesQuery($warehouseIds)
                ->whereDate('created_at', $day)
                ->sum('total');
        }
        return $data;
    }
}
