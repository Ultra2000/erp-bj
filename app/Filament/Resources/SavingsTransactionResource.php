<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingsTransactionResource\Pages;
use App\Models\SavingsTransaction;
use App\Support\Tontine;
use Filament\Facades\Filament;
use Filament\Forms;
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
