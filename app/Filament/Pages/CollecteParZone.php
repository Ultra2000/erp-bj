<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Models\User;
use App\Models\Zone;
use App\Support\Tontine;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class CollecteParZone extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Tontine';

    protected static ?string $navigationLabel = 'Collecte par zone';

    protected static ?string $title = 'Collecte par zone & par tontinier';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.collecte-par-zone';

    public static function canAccess(): bool
    {
        // Tableau de bord de pilotage : bureau / admin.
        return Tontine::canManage();
    }

    protected function getViewData(): array
    {
        return array_merge(
            static::buildData(Filament::getTenant()?->id),
            ['currency' => Filament::getTenant()->currency ?? 'FCFA'],
        );
    }

    public static function buildData(int $companyId): array
    {
        $startDay = now()->startOfDay();
        $startMonth = now()->startOfMonth();

        // --- Agrégats épargnants par zone (nb + solde) ---
        $saverAgg = Customer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('savings_balance', '>', 0)
            ->selectRaw('zone_id, COUNT(*) as savers, COALESCE(SUM(savings_balance), 0) as solde')
            ->groupBy('zone_id')
            ->get()
            ->keyBy('zone_id');

        // --- Dépôts par zone (jointure clients) ---
        $depByZone = fn ($since) => SavingsTransaction::withoutGlobalScopes()
            ->join('customers', 'customers.id', '=', 'savings_transactions.customer_id')
            ->where('savings_transactions.company_id', $companyId)
            ->where('savings_transactions.type', 'deposit')
            ->where('savings_transactions.created_at', '>=', $since)
            ->selectRaw('customers.zone_id as zone_id, COALESCE(SUM(savings_transactions.amount), 0) as total')
            ->groupBy('customers.zone_id')
            ->pluck('total', 'zone_id');

        $todayByZone = $depByZone($startDay);
        $monthByZone = $depByZone($startMonth);

        $zones = Zone::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with('tontiniers:id,name')
            ->orderBy('name')
            ->get();

        $zoneRows = $zones->map(fn (Zone $z) => [
            'name' => $z->name,
            'city' => $z->city,
            'active' => $z->is_active,
            'savers' => (int) ($saverAgg[$z->id]->savers ?? 0),
            'solde' => (int) ($saverAgg[$z->id]->solde ?? 0),
            'today' => (int) ($todayByZone[$z->id] ?? 0),
            'month' => (int) ($monthByZone[$z->id] ?? 0),
            'tontiniers' => $z->tontiniers->pluck('name')->all(),
        ])->values()->all();

        // Ligne « Sans zone » (épargnants non rattachés)
        $noZoneSavers = (int) ($saverAgg[null]->savers ?? ($saverAgg->get('')?->savers ?? 0));
        $noZoneSolde = (int) ($saverAgg[null]->solde ?? ($saverAgg->get('')?->solde ?? 0));
        if ($noZoneSavers > 0) {
            $zoneRows[] = [
                'name' => 'Sans zone',
                'city' => null,
                'active' => true,
                'savers' => $noZoneSavers,
                'solde' => $noZoneSolde,
                'today' => (int) ($todayByZone[null] ?? 0),
                'month' => (int) ($monthByZone[null] ?? 0),
                'tontiniers' => [],
            ];
        }

        // --- Par tontinier ---
        $depByAgent = fn ($since) => SavingsTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('type', 'deposit')
            ->where('created_at', '>=', $since)
            ->selectRaw('created_by, COALESCE(SUM(amount), 0) as total, COUNT(*) as nb')
            ->groupBy('created_by')
            ->get()
            ->keyBy('created_by');

        $todayByAgent = $depByAgent($startDay);
        $monthByAgent = $depByAgent($startMonth);

        $agents = User::query()
            ->whereHas('roles', fn ($r) => $r->where('roles.slug', 'tontinier')->where('model_has_roles.company_id', $companyId))
            ->with('zones:id,name,company_id')
            ->orderBy('name')
            ->get();

        $agentRows = $agents->map(fn (User $u) => [
            'name' => $u->name,
            'zones' => $u->zones->where('company_id', $companyId)->pluck('name')->all(),
            'today' => (int) ($todayByAgent[$u->id]->total ?? 0),
            'today_nb' => (int) ($todayByAgent[$u->id]->nb ?? 0),
            'month' => (int) ($monthByAgent[$u->id]->total ?? 0),
        ])->values()->all();

        return [
            'zoneRows' => $zoneRows,
            'agentRows' => $agentRows,
            'total_solde' => (int) collect($zoneRows)->sum('solde'),
            'total_today' => (int) collect($zoneRows)->sum('today'),
            'total_month' => (int) collect($zoneRows)->sum('month'),
            'total_savers' => (int) collect($zoneRows)->sum('savers'),
            'zones_count' => $zones->count(),
            'agents_count' => $agents->count(),
            'month_label' => ucfirst(now()->locale('fr')->isoFormat('MMMM YYYY')),
        ];
    }
}
