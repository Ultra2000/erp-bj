<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Paiements';
    protected static ?string $modelLabel = 'Paiement';
    protected static ?string $pluralModelLabel = 'Paiements';
    protected static ?string $icon = 'heroicon-o-currency-dollar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('payment_date')
                    ->label('Date')
                    ->default(now())
                    ->required(),
                
                Forms\Components\Select::make('payment_method')
                    ->label('Mode de paiement')
                    ->options(Payment::METHODS)
                    ->required()
                    ->default('cash')
                    ->reactive(),
                
                Forms\Components\TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->required()
                    ->prefix('FCFA')
                    ->default(function (RelationManager $livewire) {
                        // Par défaut, le montant restant à payer
                        $sale = $livewire->getOwnerRecord();
                        $remaining = $sale->total - $sale->amount_paid;
                        return $remaining > 0 ? $remaining : 0;
                    }),

                Forms\Components\TextInput::make('reference')
                    ->label('Référence / N° Chèque')
                    ->placeholder('Ex: CHQ-123456')
                    ->visible(fn (Forms\Get $get) => in_array($get('payment_method'), ['check', 'transfer', 'card'])),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull()
                    ->placeholder('Détails supplémentaires...'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode')
                    ->formatStateUsing(fn (string $state): string => Payment::METHODS[$state] ?? $state)
                    ->icon(fn (string $state): string => match ($state) {
                        'cash' => 'heroicon-o-banknotes',
                        'card' => 'heroicon-o-credit-card',
                        'check' => 'heroicon-o-ticket',
                        'transfer' => 'heroicon-o-arrows-right-left',
                        default => 'heroicon-o-currency-dollar',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'card' => 'info',
                        'check' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Enregistré par')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un paiement')
                    ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
                        $data['company_id'] = $livewire->getOwnerRecord()->company_id;
                        $data['created_by'] = auth()->id();
                        $data['account_number'] = Payment::ACCOUNTS[$data['payment_method']] ?? '512000';
                        return $data;
                    })
                    ->after(function () {
                         Notification::make() 
                            ->title('Paiement enregistré')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print_receipt')
                    ->label('Reçu')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Payment $record): string => route('payments.receipt', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
