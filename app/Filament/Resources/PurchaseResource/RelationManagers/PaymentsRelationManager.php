<?php

namespace App\Filament\Resources\PurchaseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Paiements';

    protected static ?string $modelLabel = 'Paiement';

    public function form(Form $form): Form
    {
        $companyId = Filament::getTenant()?->id;
        $purchase = $this->getOwnerRecord();
        $remaining = $purchase->remaining_amount;

        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Montant')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default($remaining)
                    ->suffix(Filament::getTenant()->currency ?? 'XOF')
                    ->helperText(fn () => 'Reste à payer : ' . number_format($remaining, 0, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'XOF')),
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Date de paiement')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('payment_method')
                    ->label('Mode de paiement')
                    ->required()
                    ->options([
                        'cash' => 'Espèces',
                        'card' => 'Carte bancaire',
                        'transfer' => 'Virement',
                        'check' => 'Chèque',
                        'mobile_money' => 'Mobile Money',
                    ]),
                Forms\Components\Select::make('bank_account_id')
                    ->label('Compte bancaire')
                    ->relationship('bankAccount', 'name', fn ($query) => $query->where('company_id', $companyId))
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('reference')
                    ->label('Référence')
                    ->maxLength(100)
                    ->placeholder('N° chèque, réf. virement...'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $currency = Filament::getTenant()->currency ?? 'XOF';

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money($currency)
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'cash' => 'Espèces',
                        'card' => 'Carte',
                        'transfer' => 'Virement',
                        'check' => 'Chèque',
                        'mobile_money' => 'Mobile Money',
                        default => $state,
                    })
                    ->color('info'),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->label('Compte')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Par')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('payment_date', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Enregistrer un paiement')
                    ->icon('heroicon-o-banknotes')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = Filament::getTenant()->id;
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->before(function (array $data) {
                        $purchase = $this->getOwnerRecord();
                        $newTotal = (float) $purchase->amount_paid + (float) $data['amount'];
                        if ($newTotal > (float) $purchase->total) {
                            Notification::make()
                                ->title('Montant trop élevé')
                                ->body('Le paiement dépasse le montant restant dû.')
                                ->danger()
                                ->send();
                            throw new Halt();
                        }
                    })
                    ->after(function () {
                        $this->getOwnerRecord()->updatePaymentStatus();
                    })
                    ->visible(fn () => $this->getOwnerRecord()->payment_status !== 'paid'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $this->getOwnerRecord()->updatePaymentStatus();
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Aucun paiement enregistré')
            ->emptyStateDescription('Cliquez sur "Enregistrer un paiement" pour ajouter un versement.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
