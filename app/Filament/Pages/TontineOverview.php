<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasTontineActions;
use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Support\Tontine;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class TontineOverview extends Page
{
    use HasTontineActions;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Tontine';

    protected static ?string $navigationLabel = 'Vue d\'ensemble';

    protected static ?string $title = 'Tontine — Vue d\'ensemble';

    protected static string $view = 'filament.pages.tontine-overview';

    public static function canAccess(): bool
    {
        return Tontine::canView();
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->tontineDepositAction(),
            $this->tontineWithdrawAction(),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('tontine.export', ['company' => $this->getCompanyId()]))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Modale (lecture seule) affichant les derniers mouvements d'épargne
     * d'un client, déclenchée depuis la liste « Top épargnants ».
     */
    public function viewMovementsAction(): Action
    {
        return Action::make('viewMovements')
            ->modalHeading(fn (array $arguments) => 'Mouvements — ' . (Customer::find($arguments['customer'] ?? null)?->name ?? 'Client'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fermer')
            ->modalWidth('2xl')
            ->modalContent(function (array $arguments) {
                $customer = Customer::withoutGlobalScopes()->find($arguments['customer'] ?? null);

                if (! $customer) {
                    return null;
                }

                $movements = SavingsTransaction::withoutGlobalScopes()
                    ->where('customer_id', $customer->id)
                    ->latest('id')
                    ->limit(15)
                    ->get();

                return view('filament.pages.tontine-customer-movements', [
                    'customer' => $customer,
                    'movements' => $movements,
                    'currency' => Filament::getTenant()->currency ?? 'FCFA',
                ]);
            });
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
