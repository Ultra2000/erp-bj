<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Company;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditCompanyProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Profil de l\'entreprise';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations Générales')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom de l\'entreprise')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('phone')
                            ->label('Téléphone'),
                        TextInput::make('address')
                            ->label('Adresse'),
                        TextInput::make('website')
                            ->label('Site Web')
                            ->url(),
                        TextInput::make('registration_number')
                            ->label('IFU (Identifiant Fiscal Unique)')
                            ->helperText('13 chiffres'),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('company-logos'),
                        Textarea::make('footer_text')
                            ->label('Texte de pied de page (Factures)'),
                        Select::make('currency')
                            ->label('Devise')
                            ->options([
                                'XOF' => 'XOF - Franc CFA (FCFA)',
                            ])
                            ->default('XOF'),
                    ]),

                Section::make('Tontine / Épargne')
                    ->description('Paramètres de la caisse d\'épargne / tontine.')
                    ->schema([
                        TextInput::make('tontine_fee')
                            ->label('Frais de tenue de compte unique')
                            ->numeric()
                            ->minValue(0)
                            ->default(2000)
                            ->suffix('FCFA')
                            ->helperText('Prélevés automatiquement, en plus, lors du tout premier dépôt d\'un client. 0 = aucun frais.'),
                    ]),

                Section::make('Fonctionnalités')
                    ->description('Activez ou désactivez les modules selon vos besoins.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('settings.modules.pos')
                                    ->label('Point de Vente (Caisse)')
                                    ->default(true),
                                Toggle::make('settings.modules.stock')
                                    ->label('Gestion de Stock (Entrepôts, Transferts)')
                                    ->default(true),
                                Toggle::make('settings.modules.hr')
                                    ->label('Ressources Humaines (Employés, Paie)')
                                    ->default(true),
                                Toggle::make('settings.modules.accounting')
                                    ->label('Comptabilité')
                                    ->default(true),
                                Toggle::make('settings.modules.banking')
                                    ->label('Gestion Bancaire')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('e-MCeF (DGI Bénin)')
                    ->description('Configuration de la facturation électronique certifiée')
                    ->schema([
                        Toggle::make('emcef_enabled')
                            ->label('Activer e-MCeF')
                            ->helperText('Active la certification des factures auprès de la DGI'),
                        Toggle::make('emcef_sandbox')
                            ->label('Mode Sandbox (Test)')
                            ->helperText('Utiliser l\'environnement de test de la DGI')
                            ->visible(fn ($get) => $get('emcef_enabled')),
                        TextInput::make('emcef_nim')
                            ->label('NIM (Numéro d\'Identification Machine)')
                            ->helperText('Fourni par la DGI lors de l\'enregistrement')
                            ->visible(fn ($get) => $get('emcef_enabled')),
                    ])
                    ->collapsed(),

                Section::make('AIB (Acompte sur Impôt Bénéfices)')
                    ->description('Configuration du prélèvement AIB sur les factures (obligatoire au Bénin)')
                    ->schema([
                        Select::make('aib_mode')
                            ->label('Mode de calcul')
                            ->options([
                                'auto' => '🔄 Automatique - Calcul selon l\'IFU du client',
                                'manual' => '✋ Manuel - Saisie manuelle sur chaque vente',
                                'disabled' => '⛔ Désactivé - Pas d\'AIB',
                            ])
                            ->default('auto')
                            ->helperText('En mode automatique : 1% si le client a un IFU, 5% sinon'),
                        Toggle::make('aib_exempt_retail')
                            ->label('Exonérer les ventes au détail')
                            ->helperText('Ne pas appliquer l\'AIB aux clients sans IFU (ventes au comptoir, particuliers)')
                            ->default(true)
                            ->visible(fn ($get) => $get('aib_mode') === 'auto'),
                    ])
                    ->collapsed(),
            ]);
    }
}
