<?php

namespace App\Filament\Concerns;

use App\Models\CashSession;
use App\Models\Customer;
use App\Support\Tontine;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;

/**
 * Actions partagées de la tontine (dépôt / retrait) + sélecteur client
 * avec création à la volée. Réutilisé par la Vue d'ensemble et la page
 * des mouvements d'épargne.
 */
trait HasTontineActions
{
    protected function tontineCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    protected function tontineCurrency(): string
    {
        return Filament::getTenant()->currency ?? 'FCFA';
    }

    /** Zones visibles (toutes pour le bureau, seulement les siennes pour un tontinier). */
    protected function tontineZoneOptions(): array
    {
        $zoneIds = \App\Support\Tontine::visibleZoneIds();

        return \App\Models\Zone::query()
            ->where('is_active', true)
            ->when($zoneIds !== null, fn ($q) => $q->whereIn('id', $zoneIds))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function tontineDefaultZoneId(): ?int
    {
        $zoneIds = \App\Support\Tontine::visibleZoneIds();

        return ($zoneIds !== null && count($zoneIds) === 1) ? (int) $zoneIds[0] : null;
    }

    protected function tontineCustomerSelect(bool $allowCreate = false): Forms\Components\Select
    {
        $companyId = $this->tontineCompanyId();
        $zoneIds = \App\Support\Tontine::visibleZoneIds();

        $select = Forms\Components\Select::make('customer_id')
            ->label('Client')
            ->required()
            ->searchable()
            ->getSearchResultsUsing(fn (string $search) => Customer::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->when($zoneIds !== null, fn ($q) => $q->whereIn('zone_id', $zoneIds))
                ->where('name', 'like', "%{$search}%")
                ->limit(30)
                ->pluck('name', 'id')
                ->all())
            ->getOptionLabelUsing(fn ($value) => Customer::find($value)?->name);

        // Pour un tontinier (périmètre borné à ses zones) : pré-afficher la liste
        // de ses épargnants sans avoir à taper.
        if ($zoneIds !== null) {
            $select->options(fn () => Customer::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereIn('zone_id', $zoneIds)
                ->orderBy('name')
                ->limit(100)
                ->pluck('name', 'id')
                ->all());
        }

        if ($allowCreate) {
            $zoneRequired = $zoneIds !== null; // un tontinier doit rattacher le client à une zone
            $select
                ->createOptionModalHeading('Nouveau client')
                ->createOptionForm([
                    \App\Filament\Resources\CustomerResource::nameInput('Nom / Raison sociale'),
                    Forms\Components\TextInput::make('phone')
                        ->label('Téléphone')->tel()->required()->maxLength(255)->placeholder('+229 XX XX XX XX'),
                    Forms\Components\Select::make('zone_id')
                        ->label('Zone')
                        ->options(fn () => $this->tontineZoneOptions())
                        ->default(fn () => $this->tontineDefaultZoneId())
                        ->required($zoneRequired)
                        ->searchable(),
                    Forms\Components\Select::make('customer_type')
                        ->label('Type')->options(['B2C' => 'Particulier (B2C)', 'B2B' => 'Professionnel (B2B)'])->default('B2C'),
                    Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255),
                    Forms\Components\TextInput::make('address')->label('Adresse')->maxLength(255),
                ])
                ->createOptionUsing(function (array $data) use ($companyId) {
                    return Customer::create([
                        'company_id' => $companyId,
                        'zone_id' => $data['zone_id'] ?? null,
                        'name' => $data['name'],
                        'phone' => $data['phone'] ?? null,
                        'customer_type' => $data['customer_type'] ?? 'B2C',
                        'email' => $data['email'] ?? null,
                        'address' => $data['address'] ?? null,
                    ])->getKey();
                });
        }

        return $select;
    }

    protected function tontineDepositAction(): Action
    {
        $currency = $this->tontineCurrency();

        return Action::make('deposit')
            ->label('Dépôt')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->visible(fn () => Tontine::canCollect())
            ->modalHeading('Enregistrer un dépôt d\'épargne')
            ->form([
                $this->tontineCustomerSelect(allowCreate: true),
                Forms\Components\TextInput::make('amount')->label('Montant')->numeric()->minValue(1)->required()->suffix($currency),
                Forms\Components\Select::make('payment_method')->label('Mode')
                    ->options(['cash' => 'Espèces', 'card' => 'Carte', 'transfer' => 'Virement', 'mobile_money' => 'Mobile Money'])
                    ->default('cash'),
                Forms\Components\TextInput::make('notes')->label('Note')->maxLength(255),
            ])
            ->action(function (array $data) use ($currency) {
                $c = Customer::findOrFail($data['customer_id']);
                $tx = \App\Support\SavingsOps::deposit($c, (int) $data['amount'], $data['payment_method'] ?? 'cash', $data['notes'] ?? null);
                Notification::make()->title('Dépôt enregistré')
                    ->body($c->name . ' — nouveau solde : ' . number_format((int) $c->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
                    ->success()
                    ->actions([
                        NotificationAction::make('receipt')
                            ->label('Imprimer le reçu')
                            ->icon('heroicon-o-printer')
                            ->url(route('savings.receipt', $tx), shouldOpenInNewTab: true),
                    ])
                    ->send();
            });
    }

    protected function tontineWithdrawAction(): Action
    {
        $currency = $this->tontineCurrency();

        return Action::make('withdraw')
            ->label('Retrait')
            ->icon('heroicon-o-minus-circle')
            ->color('warning')
            ->visible(fn () => Tontine::canManage())
            ->modalHeading('Retrait d\'épargne (espèces)')
            ->form([
                $this->tontineCustomerSelect()
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
                    $tx = \App\Support\SavingsOps::withdraw($c, (int) $data['amount'], $data['notes'] ?? null);
                    Notification::make()->title('Retrait effectué')
                        ->body($c->name . ' — nouveau solde : ' . number_format((int) $c->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
                        ->success()
                        ->actions([
                            NotificationAction::make('receipt')
                                ->label('Imprimer le reçu')
                                ->icon('heroicon-o-printer')
                                ->url(route('savings.receipt', $tx), shouldOpenInNewTab: true),
                        ])
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()->title('Retrait impossible')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
