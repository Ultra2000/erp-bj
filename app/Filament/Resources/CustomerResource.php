<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    protected static ?string $pluralModelLabel = 'Clients';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom / Raison Sociale')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('registration_number')
                            ->label('IFU (Identifiant Fiscal Unique)')
                            ->maxLength(13)
                            ->helperText('13 chiffres - Obligatoire pour facturation e-MCeF B2B')
                            ->placeholder('0000000000000'),
                        Forms\Components\Select::make('customer_type')
                            ->label('Type de client')
                            ->options([
                                'B2B' => 'Professionnel (B2B)',
                                'B2C' => 'Particulier (B2C)',
                            ])
                            ->default('B2C')
                            ->helperText('B2B = IFU obligatoire pour e-MCeF'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('+229 XX XX XX XX'),
                    ])->columns(3),
                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(255)
                            ->default('Cotonou'),
                        Forms\Components\TextInput::make('zip_code')
                            ->label('Code Postal')
                            ->maxLength(10)
                            ->placeholder('Optionnel'),
                        Forms\Components\TextInput::make('country')
                            ->label('Pays')
                            ->default('Bénin')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country_code')
                            ->label('Code Pays (ISO)')
                            ->default('BJ')
                            ->maxLength(2),
                    ])->columns(2),
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'B2B' => 'info',
                        'B2C' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'B2B' => 'Professionnel',
                        'B2C' => 'Particulier',
                        default => $state ?? 'Particulier',
                    }),
                Tables\Columns\TextColumn::make('registration_number')
                    ->label('IFU')
                    ->searchable()
                    ->placeholder('Non renseigné')
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Type de client')
                    ->options([
                        'B2B' => 'Professionnel (B2B)',
                        'B2C' => 'Particulier (B2C)',
                    ]),
                Tables\Filters\Filter::make('has_ifu')
                    ->label('Avec IFU')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('registration_number')->where('registration_number', '!=', '')),
            ])
            ->deferLoading() // Optimisation: Chargement différé via AJAX
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
