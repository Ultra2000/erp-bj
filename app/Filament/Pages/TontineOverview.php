<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class TontineOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Tontine';

    protected static ?string $navigationLabel = 'Vue d\'ensemble';

    protected static ?string $title = 'Tontine — Vue d\'ensemble';

    protected static string $view = 'filament.pages.tontine-overview';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->isAdmin() || $user->hasPermission('reports.view'));
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('tontine.export', ['company' => $this->getCompanyId()]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getViewData(): array
    {
        return array_merge(
            static::buildData($this->getCompanyId()),
            ['currency' => Filament::getTenant()->currency ?? 'FCFA'],
        );
    }

    /**
     * Données de synthèse de la tontine pour une entreprise (page + export PDF).
     */
    public static function buildData(int $companyId): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $total = (int) Customer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('savings_balance', '>', 0)
            ->sum('savings_balance');

        $savers = (int) Customer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('savings_balance', '>', 0)
            ->count();

        $sumByType = function (string $type) use ($companyId, $start, $end) {
            return (int) abs((int) SavingsTransaction::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('type', $type)
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount'));
        };

        $topSavers = Customer::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('savings_balance', '>', 0)
            ->orderByDesc('savings_balance')
            ->limit(15)
            ->get(['id', 'name', 'phone', 'savings_balance']);

        $recent = SavingsTransaction::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with('customer:id,name')
            ->latest('id')
            ->limit(20)
            ->get();

        return [
            'total' => $total,
            'savers' => $savers,
            'average' => $savers > 0 ? (int) round($total / $savers) : 0,
            'deposits_month' => $sumByType('deposit'),
            'withdrawals_month' => $sumByType('withdrawal'),
            'used_month' => $sumByType('purchase'),
            'topSavers' => $topSavers,
            'recent' => $recent,
            'month' => ucfirst($start->locale('fr')->isoFormat('MMMM YYYY')),
        ];
    }
}
