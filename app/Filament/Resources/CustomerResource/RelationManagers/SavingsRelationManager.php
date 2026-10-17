<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Customer;
use App\Models\SavingsTransaction;
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
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->isAdmin() || $user->hasPermission('pos.collect'));
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
            ->headerActions([
                Tables\Actions\Action::make('deposit')
                    ->label('Dépôt')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
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
                    ->action(function (array $data) use ($customer) {
                        $customer->deposit((int) $data['amount'], [
                            'payment_method' => $data['payment_method'] ?? 'cash',
                            'notes' => $data['notes'] ?? null,
                        ]);
                        Notification::make()->title('Dépôt enregistré')
                            ->body('Nouveau solde : ' . number_format((int) $customer->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
                            ->success()->send();
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
                            $customer->withdraw((int) $data['amount'], [
                                'payment_method' => 'cash',
                                'notes' => $data['notes'] ?? null,
                            ]);
                            Notification::make()->title('Retrait effectué')
                                ->body('Nouveau solde : ' . number_format((int) $customer->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Retrait impossible')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun mouvement d\'épargne')
            ->emptyStateDescription('Enregistrez un premier dépôt pour ce client.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
