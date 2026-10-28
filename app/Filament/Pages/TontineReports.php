<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Models\User;
use App\Models\Zone;
use App\Support\Tontine;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class TontineReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Tontine';

    protected static ?string $navigationLabel = 'Rapports';

    protected static ?string $title = 'Rapports tontine';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.tontine-reports';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public static function canAccess(): bool
    {
        return Tontine::canManage();
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summaryPdf')
                ->label('Synthèse PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('reports.tontine-summary', [
                    'company' => $this->getCompanyId(),
                    'from' => $this->start_date,
                    'until' => $this->end_date,
                ]))
                ->openUrlInNewTab(),
            Action::make('movementsPdf')
                ->label('Mouvements PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn () => route('reports.savings-movements', [
                    'company' => $this->getCompanyId(),
                    'from' => $this->start_date,
                    'until' => $this->end_date,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getViewData(): array
    {
        return array_merge(
            static::buildData($this->getCompanyId(), $this->start_date, $this->end_date),
            ['currency' => Filament::getTenant()->currency ?? 'FCFA'],
        );
    }

    /**
     * Synthèse tontine sur une période (page + export PDF).
     */
    public static function buildData(int $companyId, ?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        $byType = SavingsTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('type, COALESCE(SUM(amount), 0) as total, COUNT(*) as nb')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $sum = fn (string $t) => (int) abs((int) ($byType[$t]->total ?? 0));
        $cnt = fn (string $t) => (int) ($byType[$t]->nb ?? 0);

        // Collecte (dépôts) par zone sur la période.
        $depByZone = SavingsTransaction::withoutGlobalScopes()
            ->join('customers', 'customers.id', '=', 'savings_transactions.customer_id')
            ->where('savings_transactions.company_id', $companyId)
            ->where('savings_transactions.type', 'deposit')
            ->whereBetween('savings_transactions.created_at', [$start, $end])
            ->selectRaw('customers.zone_id as zone_id, COALESCE(SUM(savings_transactions.amount), 0) as total, COUNT(*) as nb')
            ->groupBy('customers.zone_id')
            ->get()
            ->keyBy('zone_id');

        $zones = Zone::withoutGlobalScopes()->where('company_id', $companyId)->orderBy('name')->get();
        $zoneRows = $zones->map(fn (Zone $z) => [
            'name' => $z->name,
            'total' => (int) ($depByZone[$z->id]->total ?? 0),
            'nb' => (int) ($depByZone[$z->id]->nb ?? 0),
        ])->filter(fn ($r) => $r['total'] > 0)->values()->all();

        // Collecte par tontinier sur la période.
        $depByAgent = SavingsTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('type', 'deposit')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('created_by, COALESCE(SUM(amount), 0) as total, COUNT(*) as nb')
            ->groupBy('created_by')
            ->orderByDesc('total')
            ->get();

        $agentNames = User::whereIn('id', $depByAgent->pluck('created_by'))->pluck('name', 'id');
        $agentRows = $depByAgent->map(fn ($r) => [
            'name' => $agentNames[$r->created_by] ?? ('Agent #' . $r->created_by),
            'total' => (int) $r->total,
            'nb' => (int) $r->nb,
        ])->values()->all();

        return [
            'deposited' => $sum('deposit'),
            'deposited_nb' => $cnt('deposit'),
            'withdrawn' => $sum('withdrawal'),
            'withdrawn_nb' => $cnt('withdrawal'),
            'used' => $sum('purchase'),
            'fees' => $sum('fee'),
            'fees_nb' => $cnt('fee'),
            'total_ops' => $byType->sum('nb'),
            'zoneRows' => $zoneRows,
            'agentRows' => $agentRows,
            'start' => $start,
            'end' => $end,
        ];
    }
}
