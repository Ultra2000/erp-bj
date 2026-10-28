<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingsTransactionResource\Pages;
use App\Models\SavingsTransaction;
use App\Support\SavingsCorrection;
use App\Support\Tontine;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SavingsTransactionResource extends Resource
{
    protected static ?string $model = SavingsTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Tontine';

    protected static ?string $navigationLabel = 'Mouvements d\'épargne';

    protected static ?string $modelLabel = 'Mouvement d\'épargne';

    protected static ?string $pluralModelLabel = 'Mouvements d\'épargne';

    protected static ?int $navigationSort = 2;

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

    /** Un tontinier ne voit que les mouvements des épargnants de ses zones. */
    public static function getEloquentQuery(): Builder
    {
        $zoneIds = Tontine::visibleZoneIds();

        return parent::getEloquentQuery()
            ->when($zoneIds !== null, fn (Builder $q) => $q->whereHas('customer', fn (Builder $c) => $c->whereIn('zone_id', $zoneIds)));
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
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
                    ->sortable()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '') . number_format((int) $state, 0, ',', ' ') . ' ' . $currency),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Solde après')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' ' . $currency),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Mode')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'cash' => 'Espèces',
                        'card' => 'Carte',
                        'transfer' => 'Virement',
                        'mobile_money', 'mobile' => 'Mobile Money',
                        default => $state ?? '—',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Opérateur')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Note')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(SavingsTransaction::TYPES),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Client')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Opérateur')
                    ->relationship('createdBy', 'name'),
                Tables\Filters\Filter::make('periode')
                    ->label('Période')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->filtersFormColumns(2)
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
            ->emptyStateHeading('Aucun mouvement d\'épargne')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavingsTransactions::route('/'),
        ];
    }
}
