<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Support\SavingsCorrection;
use App\Support\Tontine;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SavingsRelationManager extends RelationManager
{
    protected static string $relationship = 'savingsTransactions';

    protected static ?string $title = 'Épargne / Tontine';

    protected static ?string $icon = 'heroicon-o-banknotes';

    public function isReadOnly(): bool
    {
        return true;
    }

    protected static function canWithdraw(): bool
    {
        return Tontine::canManage();
    }

    public function table(Table $table): Table
    {
        $currency = Filament::getTenant()->currency ?? 'FCFA';
        /** @var Customer $customer */
        $customer = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('type')
            ->heading('Épargne — Solde : ' . number_format((int) $customer->savings_balance, 0, ',', ' ') . ' ' . $currency)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Opération')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'deposit', 'refund' => 'success',
                        'withdrawal' => 'danger',
                        'purchase' => 'warning',
                        'fee' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => SavingsTransaction::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->alignEnd()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '') . number_format((int) $state, 0, ',', ' ') . ' ' . $currency),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Solde après')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' ' . $currency),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Opérateur')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Note')
                    ->toggleable()
                    ->limit(40),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\Action::make('receipt')
                    ->label('Reçu')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (SavingsTransaction $record) => route('savings.receipt', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('corriger')
                    ->label('Corriger')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (SavingsTransaction $record) => Tontine::canAdjust() && SavingsCorrection::isCorrectable($record))
                    ->modalHeading('Corriger le montant')
                    ->form([
                        Forms\Components\Placeholder::make('actuel')
                            ->label('Montant actuel')
                            ->content(fn (SavingsTransaction $record) => number_format(abs((int) $record->amount), 0, ',', ' ') . ' ' . $currency),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant correct')->numeric()->minValue(1)->required()->suffix($currency),
                        Forms\Components\TextInput::make('reason')->label('Motif (optionnel)')->maxLength(255),
                    ])
                    ->action(function (SavingsTransaction $record, array $data) {
                        try {
                            SavingsCorrection::correctAmount($record, (int) $data['amount'], $data['reason'] ?? null);
                            Notification::make()->title('Montant corrigé')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Correction impossible')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('annuler')
                    ->label('Annuler')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (SavingsTransaction $record) => Tontine::canAdjust() && SavingsCorrection::isCorrectable($record))
                    ->requiresConfirmation()
                    ->modalHeading('Annuler ce mouvement')
                    ->modalDescription('Une écriture d\'ajustement inverse sera créée. Le mouvement d\'origine est conservé dans l\'historique.')
                    ->form([
                        Forms\Components\TextInput::make('reason')->label('Motif (optionnel)')->maxLength(255),
                    ])
                    ->action(function (SavingsTransaction $record, array $data) {
                        try {
                            SavingsCorrection::reverse($record, $data['reason'] ?? null);
                            Notification::make()->title('Mouvement annulé')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Annulation impossible')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('deposit')
                    ->label('Dépôt')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn () => Tontine::canManage())
                    ->modalHeading('Enregistrer un dépôt d\'épargne')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant du dépôt')
                            ->numeric()->minValue(1)->required()
                            ->suffix($currency),
                        Forms\Components\Select::make('payment_method')
                            ->label('Mode')
                            ->options(['cash' => 'Espèces', 'card' => 'Carte', 'transfer' => 'Virement', 'mobile_money' => 'Mobile Money'])
                            ->default('cash'),
                        Forms\Components\TextInput::make('notes')->label('Note')->maxLength(255),
                    ])
                    ->action(function (array $data) use ($customer, $currency) {
                        $tx = \App\Support\SavingsOps::deposit($customer, (int) $data['amount'], $data['payment_method'] ?? 'cash', $data['notes'] ?? null);
                        Notification::make()->title('Dépôt enregistré')
                            ->body('Nouveau solde : ' . number_format((int) $customer->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('receipt')
                                    ->label('Imprimer le reçu')
                                    ->icon('heroicon-o-printer')
                                    ->url(route('savings.receipt', $tx), shouldOpenInNewTab: true),
                            ])
                            ->send();
                    }),
                Tables\Actions\Action::make('withdraw')
                    ->label('Retrait')
                    ->icon('heroicon-o-minus-circle')
                    ->color('danger')
                    ->visible(fn () => static::canWithdraw())
                    ->modalHeading('Retrait d\'épargne (espèces)')
                    ->form([
                        Forms\Components\Placeholder::make('solde')
                            ->label('Solde disponible')
                            ->content(fn () => number_format((int) $customer->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant à retirer')
                            ->numeric()->minValue(1)
                            ->maxValue(fn () => (int) $customer->fresh()->savings_balance)
                            ->required()
                            ->suffix($currency),
                        Forms\Components\TextInput::make('notes')->label('Note')->maxLength(255),
                    ])
                    ->action(function (array $data) use ($customer, $currency) {
                        try {
                            $session = \App\Models\CashSession::getOpenSession($customer->company_id, auth()->id());
                            $meta = ['payment_method' => 'cash', 'notes' => $data['notes'] ?? null];
                            if ($session) {
                                $meta['cash_session_id'] = $session->id;
                            }
                            $tx = $customer->withdraw((int) $data['amount'], $meta);
                            $session?->recalculate();
                            Notification::make()->title('Retrait effectué')
                                ->body('Nouveau solde : ' . number_format((int) $customer->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
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
                Tables\Actions\Action::make('statement')
                    ->label('Relevé PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->modalHeading('Relevé de compte épargne')
                    ->modalSubmitActionLabel('Générer le PDF')
                    ->form([
                        Forms\Components\DatePicker::make('start')->label('Du')->default(now()->startOfYear())->required(),
                        Forms\Components\DatePicker::make('end')->label('Au')->default(now())->required(),
                    ])
                    ->action(function (array $data) use ($customer) {
                        $url = route('tontine.statement', [
                            'customer' => $customer->id,
                            'start' => $data['start'],
                            'end' => $data['end'],
                        ]);
                        $this->js('window.open(' . json_encode($url) . ', "_blank")');
                    }),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun mouvement d\'épargne')
            ->emptyStateDescription('Enregistrez un premier dépôt pour ce client.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
