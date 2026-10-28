<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\User;
use App\Support\Expenses;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Dépenses';

    protected static ?string $navigationLabel = 'Dépenses';

    protected static ?string $modelLabel = 'Dépense';

    protected static ?string $pluralModelLabel = 'Dépenses';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Expenses::canView();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return Expenses::canManage();
    }

    public static function canEdit($record): bool
    {
        return Expenses::canManage();
    }

    public static function canDelete($record): bool
    {
        return Expenses::canManage();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'beneficiaryUser', 'createdBy']);
    }

    public static function form(Form $form): Form
    {
        $currency = Filament::getTenant()->currency ?? 'FCFA';
        $companyId = Filament::getTenant()?->id;

        return $form->schema([
            Forms\Components\Section::make('Dépense')
                ->schema([
                    Forms\Components\DatePicker::make('spent_at')
                        ->label('Date')->default(now())->required(),
                    Forms\Components\Select::make('expense_category_id')
                        ->label('Catégorie')
                        ->relationship('category', 'name')
                        ->searchable()->preload()->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Nom')->required()->maxLength(255),
                        ]),
                    Forms\Components\TextInput::make('amount')
                        ->label('Montant')->numeric()->minValue(1)->required()->suffix($currency),
                    Forms\Components\Select::make('payment_method')
                        ->label('Mode de paiement')
                        ->options(['cash' => 'Espèces', 'mobile_money' => 'Mobile Money', 'transfer' => 'Virement', 'check' => 'Chèque', 'other' => 'Autre'])
                        ->default('cash'),
                ])->columns(2),
            Forms\Components\Section::make('Bénéficiaire')
                ->description('Choisissez un utilisateur (ex. salaire) OU saisissez un bénéficiaire libre.')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Utilisateur')
                        ->options(fn () => User::whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->placeholder('Aucun'),
                    Forms\Components\TextInput::make('beneficiary')
                        ->label('Bénéficiaire (autre)')->maxLength(255)->placeholder('Ex : Propriétaire, fournisseur…'),
                ])->columns(2),
            Forms\Components\Section::make('Détails')
                ->schema([
                    Forms\Components\TextInput::make('reference')->label('Référence / N° pièce')->maxLength(255),
                    Forms\Components\Textarea::make('notes')->label('Note')->rows(2)->columnSpanFull(),
                    Forms\Components\FileUpload::make('receipt_path')
                        ->label('Justificatif')
                        ->directory('expense-receipts')
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->downloadable()->openable()
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $currency = Filament::getTenant()->currency ?? 'FCFA';

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('spent_at')->label('Date')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Catégorie')->badge()->color('warning')->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('beneficiary_label')->label('Bénéficiaire')->placeholder('—')->searchable(['beneficiary'])->getStateUsing(fn (Expense $r) => $r->beneficiary_label),
                Tables\Columns\TextColumn::make('amount')->label('Montant')->alignEnd()->sortable()->weight('bold')->color('danger')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' ' . $currency)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' ' . $currency)),
                Tables\Columns\TextColumn::make('payment_method')->label('Mode')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'cash' => 'Espèces', 'mobile_money' => 'Mobile Money', 'transfer' => 'Virement', 'check' => 'Chèque', default => $state ?? '—',
                    })->toggleable(),
                Tables\Columns\IconColumn::make('receipt_path')->label('Justif.')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Enregistré par')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('spent_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('expense_category_id')->label('Catégorie')->relationship('category', 'name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('payment_method')->label('Mode')
                    ->options(['cash' => 'Espèces', 'mobile_money' => 'Mobile Money', 'transfer' => 'Virement', 'check' => 'Chèque', 'other' => 'Autre']),
                Tables\Filters\Filter::make('periode')
                    ->label('Période')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('until')->label('Au'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('spent_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('spent_at', '<=', $d)))
                    ->indicateUsing(function (array $data): array {
                        $ind = [];
                        if ($data['from'] ?? null) {
                            $ind[] = \Filament\Tables\Filters\Indicator::make('Du ' . \Illuminate\Support\Carbon::parse($data['from'])->format('d/m/Y'))->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $ind[] = \Filament\Tables\Filters\Indicator::make('Au ' . \Illuminate\Support\Carbon::parse($data['until'])->format('d/m/Y'))->removeField('until');
                        }
                        return $ind;
                    }),
            ])
            ->filtersFormColumns(3)
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer la sélection'),
                ]),
            ])
            ->emptyStateHeading('Aucune dépense')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
