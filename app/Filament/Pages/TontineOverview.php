<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Support\Tontine;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
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
        return Tontine::canView();
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    protected static function canWithdraw(): bool
    {
        return Tontine::canManage();
    }

    protected function customerSelect(bool $allowCreate = false): Forms\Components\Select
    {
        $companyId = $this->getCompanyId();

        $select = Forms\Components\Select::make('customer_id')
            ->label('Client')
            ->required()
            ->searchable()
            ->getSearchResultsUsing(fn (string $search) => Customer::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('name', 'like', "%{$search}%")
                ->limit(30)
                ->pluck('name', 'id')
                ->all())
            ->getOptionLabelUsing(fn ($value) => Customer::find($value)?->name);

        // Bouton « + » : créer un nouveau client sans quitter le formulaire.
        if ($allowCreate) {
            $select
                ->createOptionModalHeading('Nouveau client')
                ->createOptionForm([
                    \App\Filament\Resources\CustomerResource::nameInput('Nom / Raison sociale'),
                    Forms\Components\TextInput::make('phone')
                        ->label('Téléphone')
                        ->tel()
                        ->required()
                        ->maxLength(255)
                        ->placeholder('+229 XX XX XX XX'),
                    Forms\Components\Select::make('customer_type')
                        ->label('Type')
                        ->options(['B2C' => 'Particulier (B2C)', 'B2B' => 'Professionnel (B2B)'])
                        ->default('B2C'),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('address')
                        ->label('Adresse')
                        ->maxLength(255),
                ])
                ->createOptionUsing(function (array $data) use ($companyId) {
                    $customer = Customer::create([
                        'company_id' => $companyId,
                        'name' => $data['name'],
                        'phone' => $data['phone'] ?? null,
                        'customer_type' => $data['customer_type'] ?? 'B2C',
                        'email' => $data['email'] ?? null,
                        'address' => $data['address'] ?? null,
                    ]);

                    return $customer->getKey();
                });
        }

        return $select;
    }

    protected function getHeaderActions(): array
    {
        $currency = Filament::getTenant()->currency ?? 'FCFA';

        return [
            Action::make('deposit')
                ->label('Dépôt')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn () => Tontine::canManage())
                ->modalHeading('Enregistrer un dépôt d\'épargne')
                ->form([
                    $this->customerSelect(allowCreate: true),
                    Forms\Components\TextInput::make('amount')->label('Montant')->numeric()->minValue(1)->required()->suffix($currency),
                    Forms\Components\Select::make('payment_method')->label('Mode')
                        ->options(['cash' => 'Espèces', 'card' => 'Carte', 'transfer' => 'Virement', 'mobile_money' => 'Mobile Money'])
                        ->default('cash'),
                    Forms\Components\TextInput::make('notes')->label('Note')->maxLength(255),
                ])
                ->action(function (array $data) use ($currency) {
                    $c = Customer::findOrFail($data['customer_id']);
                    $method = $data['payment_method'] ?? 'cash';
                    $meta = ['payment_method' => $method, 'notes' => $data['notes'] ?? null];
                    $session = $method === 'cash'
                        ? \App\Models\CashSession::getOpenSession($c->company_id, auth()->id())
                        : null;
                    if ($session) {
                        $meta['cash_session_id'] = $session->id;
                    }
                    $tx = $c->deposit((int) $data['amount'], $meta);
                    $session?->recalculate();
                    Notification::make()->title('Dépôt enregistré')
                        ->body($c->name . ' — nouveau solde : ' . number_format((int) $c->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('receipt')
                                ->label('Imprimer le reçu')
                                ->icon('heroicon-o-printer')
                                ->url(route('savings.receipt', $tx), shouldOpenInNewTab: true),
                        ])
                        ->send();
                }),

            Action::make('withdraw')
                ->label('Retrait')
                ->icon('heroicon-o-minus-circle')
                ->color('warning')
                ->visible(fn () => static::canWithdraw())
                ->modalHeading('Retrait d\'épargne (espèces)')
                ->form([
                    $this->customerSelect()
                        ->helperText('Le solde disponible s\'affiche après sélection.')
                        ->live()
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('solde', $state ? (int) (Customer::find($state)?->savings_balance ?? 0) : 0)),
                    Forms\Components\Placeholder::make('solde')
                        ->label('Solde disponible')
                        ->content(fn (Forms\Get $get) => $get('customer_id')
                            ? number_format((int) (Customer::find($get('customer_id'))?->savings_balance ?? 0), 0, ',', ' ') . ' ' . $currency
                            : '—'),
                    Forms\Components\TextInput::make('amount')->label('Montant à retirer')->numeric()->minValue(1)->required()->suffix($currency),
                    Forms\Components\TextInput::make('notes')->label('Note')->maxLength(255),
                ])
                ->action(function (array $data) use ($currency) {
                    $c = Customer::findOrFail($data['customer_id']);
                    try {
                        $session = \App\Models\CashSession::getOpenSession($c->company_id, auth()->id());
                        $meta = ['payment_method' => 'cash', 'notes' => $data['notes'] ?? null];
                        if ($session) {
                            $meta['cash_session_id'] = $session->id;
                        }
                        $tx = $c->withdraw((int) $data['amount'], $meta);
                        $session?->recalculate();
                        Notification::make()->title('Retrait effectué')
                            ->body($c->name . ' — nouveau solde : ' . number_format((int) $c->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('receipt')
                                    ->label('Imprimer le reçu')
                                    ->icon('heroicon-o-printer')
                                    ->url(route('savings.receipt', $tx), shouldOpenInNewTab: true),
                            ])
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Retrait impossible')->body($e->getMessage())->danger()->send();
                    }
                }),

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
