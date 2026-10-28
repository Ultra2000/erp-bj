<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaverResource\Pages;
use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Support\SavingsOps;
use App\Support\Tontine;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaverResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Tontine';

    protected static ?string $navigationLabel = 'Épargnants';

    protected static ?string $modelLabel = 'Épargnant';

    protected static ?string $pluralModelLabel = 'Épargnants';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Tontine::canView();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    /** Clients avec un solde d'épargne ; un tontinier ne voit que ses zones. */
    public static function getEloquentQuery(): Builder
    {
        $zoneIds = Tontine::visibleZoneIds();

        return parent::getEloquentQuery()
            ->where('savings_balance', '>', 0)
            ->when($zoneIds !== null, fn (Builder $q) => $q->whereIn('zone_id', $zoneIds))
            ->with('zone')
            ->withMax('savingsTransactions as last_movement_at', 'created_at')
            ->withCount('savingsTransactions');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        $currency = Filament::getTenant()->currency ?? 'FCFA';

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-m-phone')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Zone')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('savings_balance')
                    ->label('Solde épargne')
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' ' . $currency),
                Tables\Columns\TextColumn::make('savings_transactions_count')
                    ->label('Mouvements')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('last_movement_at')
                    ->label('Dernier mouvement')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('savings_balance', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('zone_id')
                    ->label('Zone')
                    ->relationship('zone', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('depot')
                    ->label('Dépôt')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn () => Tontine::canCollect())
                    ->modalHeading(fn (Customer $record) => 'Dépôt — ' . $record->name)
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant du dépôt')->numeric()->minValue(1)->required()->suffix($currency)->autofocus(),
                        Forms\Components\Select::make('payment_method')->label('Mode')
                            ->options(['cash' => 'Espèces', 'mobile_money' => 'Mobile Money', 'card' => 'Carte', 'transfer' => 'Virement'])
                            ->default('cash'),
                        Forms\Components\TextInput::make('notes')->label('Note')->maxLength(255),
                    ])
                    ->action(function (Customer $record, array $data) use ($currency) {
                        $tx = SavingsOps::deposit($record, (int) $data['amount'], $data['payment_method'] ?? 'cash', $data['notes'] ?? null);
                        Notification::make()->title('Dépôt enregistré')
                            ->body($record->name . ' — nouveau solde : ' . number_format((int) $record->fresh()->savings_balance, 0, ',', ' ') . ' ' . $currency)
                            ->success()
                            ->actions([
                                NotificationAction::make('receipt')
                                    ->label('Imprimer le reçu')
                                    ->icon('heroicon-o-printer')
                                    ->url(route('savings.receipt', $tx), shouldOpenInNewTab: true),
                            ])
                            ->send();
                    }),
                Tables\Actions\Action::make('mouvements')
                    ->label('Mouvements')
                    ->icon('heroicon-o-list-bullet')
                    ->color('primary')
                    ->modalHeading(fn (Customer $record) => 'Mouvements — ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalWidth('2xl')
                    ->modalContent(fn (Customer $record) => view('filament.pages.tontine-customer-movements', [
                        'customer' => $record,
                        'movements' => SavingsTransaction::withoutGlobalScopes()
                            ->where('customer_id', $record->id)
                            ->latest('id')
                            ->limit(15)
                            ->get(),
                        'currency' => Filament::getTenant()->currency ?? 'FCFA',
                    ])),
                Tables\Actions\Action::make('statement')
                    ->label('Relevé PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading('Relevé de compte épargne')
                    ->modalSubmitActionLabel('Générer le PDF')
                    ->form([
                        Forms\Components\DatePicker::make('start')->label('Du')->default(now()->startOfYear())->required(),
                        Forms\Components\DatePicker::make('end')->label('Au')->default(now())->required(),
                    ])
                    ->action(function (Customer $record, array $data, $livewire) {
                        $url = route('tontine.statement', [
                            'customer' => $record->id,
                            'start' => $data['start'],
                            'end' => $data['end'],
                        ]);
                        $livewire->js('window.open(' . json_encode($url) . ', "_blank")');
                    }),
            ])
            ->emptyStateHeading('Aucun épargnant')
            ->emptyStateDescription('Aucun client n\'a de solde d\'épargne pour le moment.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavers::route('/'),
        ];
    }
}
