<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\CompanyResource\Pages;
use App\Filament\Superadmin\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de l\'entreprise')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('URL d\'accès : /admin/{slug}'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Localisation & Devise')
                    ->schema([
                        Forms\Components\Hidden::make('country_code')
                            ->default('BJ'),
                        Forms\Components\Select::make('currency')
                            ->label('Devise')
                            ->options([
                                'XOF' => 'FCFA (XOF)',
                            ])
                            ->default('XOF')
                            ->required(),
                        Forms\Components\Placeholder::make('vat_info')
                            ->label('Taux de TVA')
                            ->content('TVA normale: 18% | Exonéré: 0%'),
                    ])->columns(2),

                Forms\Components\Section::make('Identification fiscale')
                    ->schema([
                        Forms\Components\TextInput::make('registration_number')
                            ->label('IFU (Identifiant Fiscal Unique)')
                            ->maxLength(255)
                            ->helperText('13 chiffres'),
                    ])->columns(2),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Compte Actif')
                            ->default(true)
                            ->helperText('Désactiver pour suspendre l\'accès à cette entreprise.'),
                    ]),

                Forms\Components\Section::make('Modules disponibles')
                    ->description('Modules activés pour cette entreprise')
                    ->schema([
                        Forms\Components\Toggle::make('settings.modules.pos')
                            ->label('Point de Vente (POS)'),
                        Forms\Components\Toggle::make('settings.modules.stock')
                            ->label('Gestion de Stock'),
                        Forms\Components\Toggle::make('settings.modules.hr')
                            ->label('Ressources Humaines'),
                        Forms\Components\Toggle::make('settings.modules.accounting')
                            ->label('Comptabilité'),
                        Forms\Components\Toggle::make('settings.modules.banking')
                            ->label('Banque'),
                    ])->columns(3),

                Forms\Components\Section::make('Intégration e-MCeF (DGI Bénin)')
                    ->description('Configuration de la certification électronique des factures')
                    ->schema([
                        Forms\Components\Toggle::make('emcef_enabled')
                            ->label('e-MCeF activé')
                            ->helperText('Active la certification automatique des factures'),
                        Forms\Components\Toggle::make('emcef_sandbox')
                            ->label('Mode Sandbox (Test)')
                            ->helperText('Utiliser l\'environnement de test de la DGI'),
                        Forms\Components\TextInput::make('emcef_nim')
                            ->label('NIM (Numéro d\'Identification Machine)')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('emcef_token')
                            ->label('Token API e-MCeF')
                            ->rows(3)
                            ->maxLength(2000)
                            ->helperText('Token JWT fourni par la DGI'),
                        Forms\Components\DateTimePicker::make('emcef_token_expires_at')
                            ->label('Expiration du token'),
                        Forms\Components\Placeholder::make('emcef_status')
                            ->label('Statut')
                            ->content(function (?Company $record) {
                                if (!$record) return '-';
                                if (!$record->emcef_enabled) return '❌ Non activé';
                                if (empty($record->emcef_token)) return '⚠️ Token manquant';
                                if ($record->emcef_token_expires_at && $record->emcef_token_expires_at->isPast()) {
                                    return '🔴 Token expiré';
                                }
                                return '✅ Configuré' . ($record->emcef_sandbox ? ' (Sandbox)' : ' (Production)');
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('registration_number')
                    ->label('IFU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Devise')
                    ->badge()
                    ->color('success'),
                Tables\Columns\IconColumn::make('emcef_enabled')
                    ->label('e-MCeF')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('emcef_status_display')
                    ->label('Statut e-MCeF')
                    ->badge()
                    ->getStateUsing(function (Company $record) {
                        if (!$record->emcef_enabled) return 'non_active';
                        if (empty($record->emcef_token)) return 'token_manquant';
                        if ($record->emcef_token_expires_at && $record->emcef_token_expires_at->isPast()) {
                            return 'token_expire';
                        }
                        return $record->emcef_sandbox ? 'sandbox' : 'production';
                    })
                    ->colors([
                        'gray' => 'non_active',
                        'warning' => 'token_manquant',
                        'danger' => 'token_expire',
                        'info' => 'sandbox',
                        'success' => 'production',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'non_active' => 'Non activé',
                        'token_manquant' => 'Token manquant',
                        'token_expire' => 'Token expiré',
                        'sandbox' => '🧪 Sandbox',
                        'production' => '🚀 Production',
                        default => '-',
                    }),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Actif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Actives uniquement')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),
                Tables\Filters\SelectFilter::make('emcef_status')
                    ->label('Statut e-MCeF')
                    ->options([
                        'enabled' => 'e-MCeF activé',
                        'disabled' => 'e-MCeF désactivé',
                        'production' => 'En production',
                        'sandbox' => 'En sandbox',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value']) {
                            'enabled' => $query->where('emcef_enabled', true),
                            'disabled' => $query->where(function ($q) {
                                $q->where('emcef_enabled', false)->orWhereNull('emcef_enabled');
                            }),
                            'production' => $query->where('emcef_enabled', true)->where('emcef_sandbox', false),
                            'sandbox' => $query->where('emcef_enabled', true)->where('emcef_sandbox', true),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('login_as')
                    ->label('Gérer')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->url(fn (Company $record) => url('/admin/' . $record->slug))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('toggle_emcef')
                    ->label(fn (Company $record) => $record->emcef_enabled ? 'Désactiver e-MCeF' : 'Activer e-MCeF')
                    ->icon(fn (Company $record) => $record->emcef_enabled ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                    ->color(fn (Company $record) => $record->emcef_enabled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Company $record) {
                        $record->update(['emcef_enabled' => !$record->emcef_enabled]);
                    }),
                Tables\Actions\Action::make('test_emcef')
                    ->label('Tester e-MCeF')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->visible(fn (Company $record) => $record->emcef_enabled && !empty($record->emcef_token))
                    ->action(function (Company $record) {
                        $result = \App\Services\EmcefService::testConnection(
                            $record->emcef_token,
                            $record->emcef_sandbox ?? true
                        );
                        
                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Connexion e-MCeF réussie')
                                ->body('NIM: ' . ($result['nim'] ?? 'N/A'))
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Erreur de connexion e-MCeF')
                                ->body($result['error'] ?? 'Erreur inconnue')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reset_data')
                    ->label('Réinitialiser')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Réinitialiser l\'entreprise')
                    ->modalDescription(fn (Company $record) => new \Illuminate\Support\HtmlString(
                        'Toutes les données métier de <strong>' . e($record->name) . '</strong> seront <strong>supprimées définitivement</strong> : '
                        . 'produits, ventes, achats, clients, fournisseurs, stock, factures, devis, caisse, comptabilité.<br><br>'
                        . 'L\'entreprise, ses utilisateurs, ses rôles et ses entrepôts sont <strong>conservés</strong>. Les autres entreprises ne sont pas affectées.<br><br>'
                        . '<span style="color:#b91c1c;font-weight:600;">Cette action est irréversible.</span>'
                    ))
                    ->modalSubmitActionLabel('Oui, tout réinitialiser')
                    ->form([
                        Forms\Components\TextInput::make('confirmation')
                            ->label('Tapez le nom exact de l\'entreprise pour confirmer')
                            ->required()
                            ->rules([fn (Company $record) => 'in:' . $record->name])
                            ->validationMessages(['in' => 'Le nom saisi ne correspond pas à l\'entreprise.']),
                    ])
                    ->action(function (Company $record) {
                        $record->resetBusinessData();
                        \Filament\Notifications\Notification::make()
                            ->title('Entreprise réinitialisée')
                            ->body('Toutes les données métier de « ' . $record->name . ' » ont été supprimées.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
