<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use App\Models\Payment;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->status !== 'completed'),
            
            Actions\Action::make('add_payment')
                ->label('Ajouter un paiement')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn () => $this->record->payment_status !== 'paid')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('payment_date')
                        ->label('Date')
                        ->default(now())
                        ->required(),
                    
                    \Filament\Forms\Components\Select::make('payment_method')
                        ->label('Mode de paiement')
                        ->options(Payment::METHODS)
                        ->required()
                        ->default('cash')
                        ->reactive(),
                    
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Montant')
                        ->numeric()
                        ->required()
                        ->prefix('FCFA')
                        ->default(fn () => $this->record->total - $this->record->amount_paid),

                    \Filament\Forms\Components\TextInput::make('reference')
                        ->label('Référence / N° Chèque')
                        ->placeholder('Ex: CHQ-123456'),
                    
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data) {
                    $this->record->payments()->create([
                        'company_id' => $this->record->company_id,
                        'amount' => $data['amount'],
                        'payment_method' => $data['payment_method'],
                        'payment_date' => $data['payment_date'],
                        'reference' => $data['reference'] ?? null,
                        'account_number' => Payment::ACCOUNTS[$data['payment_method']] ?? '512000',
                        'notes' => $data['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Paiement enregistré')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informations de la facture')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('invoice_number')
                                    ->label('N° Facture')
                                    ->weight('bold'),
                                Components\TextEntry::make('customer.name')
                                    ->label('Client'),
                                Components\TextEntry::make('warehouse.name')
                                    ->label('Entrepôt'),
                                Components\TextEntry::make('created_at')
                                    ->label('Date')
                                    ->date('d/m/Y H:i'),
                                Components\TextEntry::make('status')
                                    ->label('Statut')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'completed' => 'Terminée',
                                        'pending' => 'En attente',
                                        'cancelled' => 'Annulée',
                                        default => $state,
                                    }),
                            ]),
                    ]),

                Components\Section::make('Montants et Paiements')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('total')
                                    ->label('Total TTC')
                                    ->money('XOF')
                                    ->weight('bold')
                                    ->size(Components\TextEntry\TextEntrySize::Large),
                                Components\TextEntry::make('amount_paid')
                                    ->label('Montant payé')
                                    ->money('XOF')
                                    ->color('success')
                                    ->weight('bold'),
                                Components\TextEntry::make('remaining')
                                    ->label('Reste à payer')
                                    ->state(fn ($record) => $record->total - $record->amount_paid)
                                    ->money('XOF')
                                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                                    ->weight('bold'),
                                Components\TextEntry::make('payment_status')
                                    ->label('Statut paiement')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'partial' => 'warning',
                                        'pending' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'paid' => 'Payé',
                                        'partial' => 'Partiel',
                                        'pending' => 'Non payé',
                                        default => $state,
                                    }),
                            ]),
                    ]),

                Components\Section::make('Historique des paiements')
                    ->schema([
                        Components\RepeatableEntry::make('payments')
                            ->label('')
                            ->schema([
                                Components\Grid::make(5)
                                    ->schema([
                                        Components\TextEntry::make('payment_date')
                                            ->label('Date')
                                            ->date('d/m/Y'),
                                        Components\TextEntry::make('payment_method')
                                            ->label('Mode')
                                            ->formatStateUsing(fn (string $state): string => Payment::METHODS[$state] ?? $state)
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'cash' => 'success',
                                                'card' => 'info',
                                                'check' => 'warning',
                                                default => 'gray',
                                            }),
                                        Components\TextEntry::make('amount')
                                            ->label('Montant')
                                            ->money('XOF')
                                            ->weight('bold'),
                                        Components\TextEntry::make('reference')
                                            ->label('Référence'),
                                        Components\TextEntry::make('creator.name')
                                            ->label('Par'),
                                    ]),
                            ])
                            ->placeholder('Aucun paiement enregistré'),
                    ])
                    ->collapsible(),
            ]);
    }
}
