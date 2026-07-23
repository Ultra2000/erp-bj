<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Cashier\CashRegisterPage;
use App\Models\Customer;
use App\Models\Sale;
use Filament\Actions\Action as HeaderAction;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CreancesClients extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Créances clients';

    protected static ?string $title = 'Suivi des créances clients';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.creances-clients';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasPermission('sales.view'));
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::baseDebtorQuery()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected function getHeaderActions(): array
    {
        $companyId = Filament::getTenant()?->id;

        return [
            HeaderAction::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->url(fn () => route('reports.receivables.pdf', ['company' => $companyId]))
                ->openUrlInNewTab(),

            HeaderAction::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn () => route('reports.receivables.excel', ['company' => $companyId])),
        ];
    }

    /**
     * Condition SQL (portable MySQL/SQLite) identifiant une vente qui constitue une dette :
     * vente finalisée, hors avoir, dont le net à payer (TTC + AIB − déjà payé) reste positif.
     */
    protected static function debtConditionSql(): string
    {
        return "sales.status = 'completed'"
            . " AND (sales.type IS NULL OR sales.type <> 'credit_note')"
            . " AND ((sales.total + COALESCE(sales.aib_amount, 0)) - COALESCE(sales.amount_paid, 0)) > 0.5";
    }

    /**
     * Requête de base : clients ayant au moins une dette, avec montant dû,
     * nombre de factures impayées et date de la plus ancienne dette.
     */
    protected static function debtorsBuilderFor(int $companyId): Builder
    {
        $cond = static::debtConditionSql();

        return Customer::withoutGlobalScopes()
            ->where('customers.company_id', $companyId)
            ->select('customers.*')
            ->selectRaw("(SELECT COALESCE(SUM((sales.total + COALESCE(sales.aib_amount, 0)) - COALESCE(sales.amount_paid, 0)), 0) FROM sales WHERE sales.customer_id = customers.id AND {$cond}) as debt_total")
            ->selectRaw("(SELECT COUNT(*) FROM sales WHERE sales.customer_id = customers.id AND {$cond}) as debt_count")
            ->selectRaw("(SELECT MIN(sales.created_at) FROM sales WHERE sales.customer_id = customers.id AND {$cond}) as oldest_debt_date")
            ->whereRaw("EXISTS (SELECT 1 FROM sales WHERE sales.customer_id = customers.id AND {$cond})");
    }

    protected static function baseDebtorQuery(): Builder
    {
        return static::debtorsBuilderFor((int) (Filament::getTenant()?->id));
    }

    /**
     * Données normalisées des créances d'une entreprise (pour les exports PDF/CSV).
     */
    public static function debtorsForCompany(int $companyId): array
    {
        return static::debtorsBuilderFor($companyId)
            ->orderByDesc('debt_total')
            ->get()
            ->map(function (Customer $c) {
                $days = $c->oldest_debt_date
                    ? (int) Carbon::parse($c->oldest_debt_date)->startOfDay()->diffInDays(now()->startOfDay())
                    : 0;
                return [
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'registration_number' => $c->registration_number,
                    'debt_count' => (int) $c->debt_count,
                    'debt_total' => (float) $c->debt_total,
                    'oldest_date' => $c->oldest_debt_date ? Carbon::parse($c->oldest_debt_date)->format('d/m/Y') : '-',
                    'days' => $days,
                ];
            })
            ->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::baseDebtorQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Customer $record) => $record->registration_number ? 'IFU : ' . $record->registration_number : null),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Numéro copié')
                    ->placeholder('—'),

                TextColumn::make('debt_count')
                    ->label('Factures')
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('debt_total')
                    ->label('Montant dû')
                    ->alignEnd()
                    ->weight('bold')
                    ->color('danger')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' FCFA'),

                TextColumn::make('oldest_debt_date')
                    ->label('Ancienneté')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $this->ageColor($this->daysSince($state)))
                    ->formatStateUsing(function ($state) {
                        $days = $this->daysSince($state);
                        if ($days === null) {
                            return '—';
                        }
                        return $days . ' jour' . ($days > 1 ? 's' : '');
                    }),

                TextColumn::make('oldest_debt_date_display')
                    ->label('Depuis le')
                    ->state(fn (Customer $record) => $record->oldest_debt_date
                        ? Carbon::parse($record->oldest_debt_date)->format('d/m/Y')
                        : '—'),
            ])
            ->filters([
                SelectFilter::make('anciennete')
                    ->label('Ancienneté minimale')
                    ->options([
                        '7' => 'Plus de 7 jours',
                        '15' => 'Plus de 15 jours',
                        '30' => 'Plus de 30 jours',
                        '60' => 'Plus de 60 jours',
                        '90' => 'Plus de 90 jours',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        $cutoff = now()->subDays((int) $data['value'])->toDateTimeString();
                        $cond = static::debtConditionSql();
                        return $query->whereRaw(
                            "EXISTS (SELECT 1 FROM sales WHERE sales.customer_id = customers.id AND {$cond} AND sales.created_at <= ?)",
                            [$cutoff]
                        );
                    }),
            ])
            ->actions([
                Action::make('encaisser')
                    ->label('Encaisser')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->url(fn (Customer $record) => CashRegisterPage::getUrl([
                        'tab' => 'encaisser',
                        'client' => $record->name,
                    ]))
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->hasPermission('pos.collect')),

                Action::make('detail')
                    ->label('Détail')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->modalHeading(fn (Customer $record) => 'Factures impayées — ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn (Customer $record) => view('filament.pages.creances-client-detail', [
                        'customer' => $record,
                        'sales' => static::unpaidSalesForCustomer($record->id),
                    ])),
            ])
            ->defaultSort('debt_total', 'desc')
            ->striped()
            ->emptyStateHeading('Aucune créance')
            ->emptyStateDescription('Toutes les factures clients sont soldées.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * Ventes impayées d'un client, de la plus ancienne à la plus récente.
     */
    public static function unpaidSalesForCustomer(int $customerId)
    {
        $cond = static::debtConditionSql();

        return Sale::withoutGlobalScopes()
            ->where('customer_id', $customerId)
            ->whereRaw('(' . $cond . ')')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function (Sale $sale) {
                $net = (float) $sale->total + (float) ($sale->aib_amount ?? 0);
                $remaining = max(0, $net - (float) $sale->amount_paid);
                return [
                    'invoice_number' => $sale->invoice_number,
                    'date' => $sale->created_at->format('d/m/Y'),
                    'days' => (int) $sale->created_at->startOfDay()->diffInDays(now()->startOfDay()),
                    'total' => $net,
                    'paid' => (float) $sale->amount_paid,
                    'remaining' => $remaining,
                    'payment_status' => $sale->payment_status,
                ];
            });
    }

    /**
     * Synthèse pour les cartes en haut de page.
     */
    public function getDebtSummary(): array
    {
        $rows = static::baseDebtorQuery()->get();

        $oldest = $rows
            ->map(fn ($r) => $this->daysSince($r->oldest_debt_date))
            ->filter(fn ($d) => $d !== null)
            ->max();

        return [
            'total' => (float) $rows->sum('debt_total'),
            'customers' => $rows->count(),
            'invoices' => (int) $rows->sum('debt_count'),
            'oldest_days' => $oldest ?? 0,
        ];
    }

    protected function daysSince($date): ?int
    {
        if (empty($date)) {
            return null;
        }
        return (int) Carbon::parse($date)->startOfDay()->diffInDays(now()->startOfDay());
    }

    protected function ageColor(?int $days): string
    {
        return match (true) {
            $days === null => 'gray',
            $days > 30 => 'danger',
            $days > 15 => 'warning',
            default => 'gray',
        };
    }
}
