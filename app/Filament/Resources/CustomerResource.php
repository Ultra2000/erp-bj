<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Services\EmcefService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
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

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasPermission('customers.view'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Champ « nom » réutilisable, imposant un nom bien identifiable :
     * obligatoire, au moins 2 lettres (pas seulement des chiffres/ponctuation),
     * espaces superflus retirés.
     */
    public static function nameInput(string $label = 'Nom / Raison Sociale'): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('name')
            ->label($label)
            ->required()
            ->minLength(2)
            ->maxLength(255)
            ->rule('regex:/\p{L}.*\p{L}/u')
            ->dehydrateStateUsing(fn (?string $state) => trim((string) $state))
            ->validationMessages([
                'required' => 'Le nom du client est obligatoire.',
                'min' => 'Le nom doit comporter au moins 2 caractères.',
                'regex' => 'Saisissez un nom identifiable (au moins 2 lettres).',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('registration_number')
                            ->label('IFU (Identifiant Fiscal Unique)')
                            ->maxLength(13)
                            ->helperText('13 chiffres — Cliquez sur la loupe pour rechercher automatiquement les informations')
                            ->placeholder('0000000000000')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Synchroniser registration_number → tax_number pour e-MCeF
                                if ($state) {
                                    $set('tax_number', $state);
                                }
                            })
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('lookupIfu')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->tooltip('Rechercher les informations via e-MCeF (DGI)')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $ifu = $get('registration_number');

                                        if (!$ifu || strlen($ifu) !== 13) {
                                            Notification::make()
                                                ->warning()
                                                ->title('IFU invalide')
                                                ->body('L\'IFU doit contenir exactement 13 chiffres.')
                                                ->send();
                                            return;
                                        }

                                        $company = Filament::getTenant();
                                        if (!$company?->emcef_enabled || !$company->emcef_token) {
                                            Notification::make()
                                                ->warning()
                                                ->title('e-MCeF non configuré')
                                                ->body('Activez e-MCeF dans les paramètres pour utiliser la recherche IFU.')
                                                ->send();
                                            return;
                                        }

                                        $emcef = new EmcefService($company);
                                        $result = $emcef->verifyIfu($ifu);

                                        if ($result['success'] && !empty($result['data'])) {
                                            $data = $result['data'];

                                            // Remplir les champs avec les données récupérées
                                            if (!empty($data['raisonSociale'] ?? $data['name'] ?? null)) {
                                                $set('name', $data['raisonSociale'] ?? $data['name']);
                                            }
                                            if (!empty($data['adresse'] ?? $data['address'] ?? null)) {
                                                $set('address', $data['adresse'] ?? $data['address']);
                                            }
                                            if (!empty($data['telephone'] ?? $data['phone'] ?? null)) {
                                                $set('phone', $data['telephone'] ?? $data['phone']);
                                            }
                                            if (!empty($data['email'] ?? null)) {
                                                $set('email', $data['email']);
                                            }
                                            if (!empty($data['ville'] ?? $data['city'] ?? null)) {
                                                $set('city', $data['ville'] ?? $data['city']);
                                            }

                                            // Automatiquement B2B pour un contribuable avec IFU
                                            $set('customer_type', 'B2B');
                                            $set('tax_number', $ifu);

                                            Notification::make()
                                                ->success()
                                                ->title('IFU trouvé')
                                                ->body('Les informations du contribuable ont été remplies automatiquement.')
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->danger()
                                                ->title('IFU non trouvé')
                                                ->body($result['error'] ?? 'Aucune information trouvée pour cet IFU.')
                                                ->send();
                                        }
                                    })
                            ),
                        static::nameInput(),
                        Forms\Components\Select::make('customer_type')
                            ->label('Type de client')
                            ->options([
                                'B2B' => 'Professionnel (B2B)',
                                'B2C' => 'Particulier (B2C)',
                            ])
                            ->default('B2C')
                            ->helperText('B2B = IFU obligatoire pour e-MCeF'),
                        Forms\Components\Select::make('zone_id')
                            ->label('Zone (tontine)')
                            ->relationship('zone', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Aucune')
                            ->helperText('Zone de collecte de l\'épargnant'),
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
                        Forms\Components\Hidden::make('tax_number'),
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

    /**
     * Fiche client en LECTURE SEULE (page « Voir ») — aucun champ modifiable,
     * pour consulter les infos + l'épargne sans risque de modification accidentelle.
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        $currency = Filament::getTenant()->currency ?? 'FCFA';

        return $infolist
            ->schema([
                Infolists\Components\Section::make('Épargne / Tontine')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Infolists\Components\TextEntry::make('savings_balance')
                            ->label('Solde d\'épargne disponible')
                            ->badge()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->color(fn ($state) => (int) $state > 0 ? 'success' : 'gray')
                            ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' ' . $currency),
                    ]),
                Infolists\Components\Section::make('Informations générales')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nom / Raison Sociale')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('customer_type')
                            ->label('Type de client')
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'B2B' => 'info',
                                'B2C' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state) => match ($state) {
                                'B2B' => 'Professionnel (B2B)',
                                'B2C' => 'Particulier (B2C)',
                                default => $state ?? 'Particulier',
                            }),
                        Infolists\Components\TextEntry::make('registration_number')
                            ->label('IFU')
                            ->placeholder('Non renseigné')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Téléphone')
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->placeholder('Non renseigné')
                            ->copyable(),
                    ])->columns(2),
                Infolists\Components\Section::make('Adresse')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Infolists\Components\TextEntry::make('address')
                            ->label('Adresse')
                            ->placeholder('Non renseignée')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('city')
                            ->label('Ville')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('country')
                            ->label('Pays')
                            ->placeholder('—'),
                    ])->columns(2)->collapsible(),
                Infolists\Components\Section::make('Notes')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('Aucune note')
                            ->columnSpanFull(),
                    ])->collapsible()->collapsed(),
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
                Tables\Columns\TextColumn::make('savings_balance')
                    ->label('Solde épargne')
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'FCFA')),
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
            ->recordUrl(fn (Customer $record) => static::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->color('primary'),
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assignZone')
                        ->label('Affecter une zone')
                        ->icon('heroicon-o-map-pin')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('zone_id')
                                ->label('Zone')
                                ->options(fn () => \App\Models\Zone::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required()
                                ->helperText('La zone sera appliquée à tous les clients sélectionnés.'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(fn (Customer $c) => $c->update(['zone_id' => $data['zone_id']]));
                            Notification::make()
                                ->title('Zone affectée')
                                ->body($records->count() . ' client(s) rattaché(s) à la zone.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SavingsRelationManager::class,
            RelationManagers\PurchasedProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
