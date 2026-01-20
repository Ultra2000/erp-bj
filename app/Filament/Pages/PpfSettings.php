<?php

namespace App\Filament\Pages;

use App\Services\EmcefService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class PpfSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Configuration e-MCeF';
    protected static ?string $title = 'Certification électronique des factures (e-MCeF)';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.ppf-settings';

    public static function shouldRegisterNavigation(): bool
    {
        // Toujours afficher dans le menu pour la version Bénin
        return true;
    }

    public static function canAccess(): bool
    {
        // Toujours accessible
        return true;
    }

    public ?array $data = [];
    public bool $connectionStatus = false;
    public ?array $statusInfo = null;

    public function mount(): void
    {
        $company = Filament::getTenant();

        $this->form->fill([
            'emcef_enabled' => $company->emcef_enabled ?? false,
            'emcef_sandbox' => $company->emcef_sandbox ?? true,
            'emcef_token' => $company->emcef_token,
            'emcef_nim' => $company->emcef_nim,
            'tax_number' => $company->tax_number,
        ]);

        // Vérifier le statut de la connexion si configuré
        if ($company->emcef_enabled && $company->emcef_token) {
            $this->checkConnectionStatus();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Activation e-MCeF')
                    ->description('Activez la certification électronique des factures conformément à la réglementation béninoise')
                    ->schema([
                        Forms\Components\Toggle::make('emcef_enabled')
                            ->label('Activer e-MCeF')
                            ->helperText('Une fois activé, toutes vos factures seront automatiquement certifiées par la DGI')
                            ->live(),
                            
                        Forms\Components\Toggle::make('emcef_sandbox')
                            ->label('Mode Test (Sandbox)')
                            ->helperText('Utilisez le mode test pour vos essais. Désactivez pour la production.')
                            ->default(true)
                            ->visible(fn (Forms\Get $get) => $get('emcef_enabled')),
                    ]),

                Forms\Components\Section::make('Informations fiscales')
                    ->description('Ces informations sont utilisées pour la certification des factures')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->label('IFU (Identifiant Fiscal Unique)')
                            ->maxLength(13)
                            ->required()
                            ->regex('/^\d{13}$/')
                            ->helperText('Votre numéro IFU à 13 chiffres'),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('emcef_enabled')),

                Forms\Components\Section::make('Configuration API')
                    ->description('Renseignez votre token e-MCeF fourni par la DGI')
                    ->schema([
                        Forms\Components\TextInput::make('emcef_nim')
                            ->label('NIM (Numéro d\'Identification Machine)')
                            ->helperText('Numéro d\'identification de votre machine de facturation'),
                            
                        Forms\Components\Textarea::make('emcef_token')
                            ->label('Token API e-MCeF')
                            ->rows(3)
                            ->required()
                            ->helperText('Le token JWT fourni par la DGI pour authentifier vos requêtes'),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('emcef_enabled')),

                Forms\Components\Section::make('📖 Guide de configuration e-MCeF')
                    ->schema([
                        Forms\Components\Placeholder::make('guide')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="space-y-4 text-sm">
                                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-2">Qu\'est-ce que e-MCeF ?</h4>
                                        <p class="text-blue-600 dark:text-blue-400">
                                            Le Mécanisme de Certification électronique des Factures (e-MCeF) est le système de la Direction Générale des Impôts du Bénin 
                                            pour la certification obligatoire de toutes les factures émises par les contribuables.
                                        </p>
                                    </div>

                                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <h4 class="font-semibold text-green-700 dark:text-green-300 mb-2">Comment obtenir votre token ?</h4>
                                        <ol class="list-decimal list-inside space-y-1 text-green-600 dark:text-green-400">
                                            <li>Rendez-vous sur le <a href="https://developper.impots.bj" target="_blank" class="underline font-medium">portail développeur DGI</a></li>
                                            <li>Créez un compte ou connectez-vous avec vos identifiants</li>
                                            <li>Enregistrez votre application et votre NIM</li>
                                            <li>Générez votre token API</li>
                                            <li>Copiez le token et collez-le ci-dessus</li>
                                        </ol>
                                    </div>

                                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                                        <h4 class="font-semibold text-amber-700 dark:text-amber-300 mb-2">⚠️ Mode Sandbox vs Production</h4>
                                        <ul class="list-disc list-inside space-y-1 text-amber-600 dark:text-amber-400">
                                            <li><strong>Mode Sandbox</strong> : Pour les tests. Les factures ne sont pas réellement certifiées.</li>
                                            <li><strong>Mode Production</strong> : Pour l\'utilisation réelle. Les factures sont certifiées officiellement.</li>
                                            <li>Commencez toujours par le mode Sandbox pour valider votre configuration.</li>
                                        </ul>
                                    </div>

                                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                        <h4 class="font-semibold text-purple-700 dark:text-purple-300 mb-2">Liens utiles</h4>
                                        <ul class="list-disc list-inside space-y-1 text-purple-600 dark:text-purple-400">
                                            <li><a href="https://developper.impots.bj" target="_blank" class="underline">Portail développeur DGI</a></li>
                                            <li><a href="https://developper.impots.bj/sygmef-emcf/swagger/index.html" target="_blank" class="underline">Documentation API Swagger</a></li>
                                            <li><a href="https://www.impots.finances.gouv.bj" target="_blank" class="underline">Site officiel DGI Bénin</a></li>
                                        </ul>
                                    </div>
                                </div>
                            ')),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $company = Filament::getTenant();

        // Si le token est fourni, tester la connexion d'abord
        if ($data['emcef_enabled'] && !empty($data['emcef_token'])) {
            $testResult = EmcefService::testConnection(
                $data['emcef_token'],
                $data['emcef_sandbox'] ?? true
            );

            if (!$testResult['success']) {
                Notification::make()
                    ->title('Erreur de connexion')
                    ->body($testResult['error'] ?? 'Impossible de se connecter à l\'API e-MCeF. Vérifiez votre token.')
                    ->danger()
                    ->send();
                return;
            }

            // Mettre à jour les informations récupérées
            $company->update([
                'emcef_enabled' => $data['emcef_enabled'],
                'emcef_sandbox' => $data['emcef_sandbox'] ?? true,
                'emcef_token' => $data['emcef_token'],
                'emcef_nim' => $data['emcef_nim'] ?? $testResult['nim'] ?? $company->emcef_nim,
                'tax_number' => $data['tax_number'] ?? $company->tax_number,
                'emcef_token_expires_at' => isset($testResult['token_valid_until']) 
                    ? \Carbon\Carbon::parse($testResult['token_valid_until']) 
                    : null,
            ]);

            $this->statusInfo = $testResult['data'] ?? null;
            $this->connectionStatus = true;

            Notification::make()
                ->title('Configuration e-MCeF enregistrée')
                ->body('La connexion à l\'API e-MCeF a été vérifiée avec succès.')
                ->success()
                ->send();
        } else {
            $company->update([
                'emcef_enabled' => $data['emcef_enabled'] ?? false,
                'emcef_sandbox' => $data['emcef_sandbox'] ?? true,
                'emcef_token' => $data['emcef_token'] ?? null,
                'emcef_nim' => $data['emcef_nim'] ?? $company->emcef_nim,
                'tax_number' => $data['tax_number'] ?? $company->tax_number,
            ]);

            Notification::make()
                ->title('Configuration enregistrée')
                ->success()
                ->send();
        }

        // Rafraîchir le formulaire
        $this->mount();
    }

    public function testConnection(): void
    {
        $data = $this->form->getState();
        $company = Filament::getTenant();

        if (empty($data['emcef_token'])) {
            Notification::make()
                ->title('Token requis')
                ->body('Veuillez d\'abord saisir votre token API e-MCeF. Obtenez-le sur https://developper.impots.bj')
                ->warning()
                ->persistent()
                ->send();
            return;
        }

        $result = EmcefService::testConnection(
            $data['emcef_token'],
            $data['emcef_sandbox'] ?? true
        );

        if ($result['success']) {
            $this->connectionStatus = true;
            $this->statusInfo = $result['data'] ?? null;

            $message = 'Connexion réussie !';
            if (isset($result['nim'])) {
                $message .= ' NIM : ' . $result['nim'];
            }
            if (isset($result['token_valid_until'])) {
                $message .= ' | Token valide jusqu\'au : ' . \Carbon\Carbon::parse($result['token_valid_until'])->format('d/m/Y H:i');
            }

            Notification::make()
                ->title('Test de connexion réussi')
                ->body($message)
                ->success()
                ->persistent()
                ->send();
        } else {
            $this->connectionStatus = false;
            $this->statusInfo = null;

            $errorBody = $result['error'] ?? 'Erreur inconnue';
            if (!empty($result['help'])) {
                $errorBody .= "\n\n💡 " . $result['help'];
            }

            Notification::make()
                ->title('Échec du test de connexion')
                ->body($errorBody)
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function checkConnectionStatus(): void
    {
        $company = Filament::getTenant();
        $service = new EmcefService($company);
        $status = $service->getStatus();

        $this->connectionStatus = $status['success'];
        $this->statusInfo = $status['data'] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label('Tester la connexion')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action('testConnection'),
                
            Action::make('view_pending')
                ->label('Voir factures en attente')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->action('viewPendingInvoices')
                ->visible(fn () => $this->connectionStatus),
        ];
    }

    public function viewPendingInvoices(): void
    {
        $company = Filament::getTenant();
        $service = new EmcefService($company);
        $result = $service->getPendingInvoices();

        if ($result['success']) {
            $count = $result['data']['pendingRequestsCount'] ?? 0;
            
            if ($count > 0) {
                Notification::make()
                    ->title('Factures en attente')
                    ->body("{$count} facture(s) en attente de confirmation")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Aucune facture en attente')
                    ->body('Toutes vos factures sont à jour.')
                    ->success()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('Erreur')
                ->body($result['error'] ?? 'Erreur lors de la récupération')
                ->danger()
                ->send();
        }
    }
}
