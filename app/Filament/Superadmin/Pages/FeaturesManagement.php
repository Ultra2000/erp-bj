<?php

namespace App\Filament\Superadmin\Pages;

use App\Models\Company;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class FeaturesManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Gestion des Fonctionnalités';
    protected static ?string $title = 'Gestion des Fonctionnalités';
    protected static ?string $slug = 'features-management';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.superadmin.pages.features-management';

    public ?array $globalFeatures = [];
    public ?int $selectedCompanyId = null;
    public ?array $companyFeatures = [];

    /**
     * Liste de toutes les fonctionnalités disponibles dans le système
     */
    public static function getAvailableFeatures(): array
    {
        return [
            // Ventes
            'sales' => [
                'label' => 'Ventes',
                'description' => 'Gestion des ventes et factures clients',
                'icon' => 'heroicon-o-shopping-cart',
                'category' => 'core',
            ],
            'quotes' => [
                'label' => 'Devis',
                'description' => 'Création et gestion des devis',
                'icon' => 'heroicon-o-document-text',
                'category' => 'core',
            ],
            'delivery_notes' => [
                'label' => 'Bons de Livraison',
                'description' => 'Génération des bons de livraison',
                'icon' => 'heroicon-o-truck',
                'category' => 'core',
            ],
            'recurring_orders' => [
                'label' => 'Commandes Récurrentes',
                'description' => 'Gestion des commandes automatiques',
                'icon' => 'heroicon-o-arrow-path',
                'category' => 'core',
            ],

            // Stocks & Achats
            'products' => [
                'label' => 'Produits',
                'description' => 'Gestion du catalogue produits',
                'icon' => 'heroicon-o-cube',
                'category' => 'stock',
            ],
            'purchases' => [
                'label' => 'Achats',
                'description' => 'Gestion des achats fournisseurs',
                'icon' => 'heroicon-o-shopping-bag',
                'category' => 'stock',
            ],
            'suppliers' => [
                'label' => 'Fournisseurs',
                'description' => 'Gestion des fournisseurs',
                'icon' => 'heroicon-o-building-office',
                'category' => 'stock',
            ],
            'warehouses' => [
                'label' => 'Entrepôts',
                'description' => 'Gestion multi-entrepôts',
                'icon' => 'heroicon-o-home-modern',
                'category' => 'stock',
            ],
            'stock_transfers' => [
                'label' => 'Transferts de Stock',
                'description' => 'Transferts entre entrepôts',
                'icon' => 'heroicon-o-arrows-right-left',
                'category' => 'stock',
            ],
            'inventory' => [
                'label' => 'Inventaire',
                'description' => 'Gestion des inventaires',
                'icon' => 'heroicon-o-clipboard-document-list',
                'category' => 'stock',
            ],

            // Point de Vente
            'pos' => [
                'label' => 'Caisse (POS)',
                'description' => 'Point de vente et encaissement',
                'icon' => 'heroicon-o-computer-desktop',
                'category' => 'pos',
            ],
            'cash_sessions' => [
                'label' => 'Sessions de Caisse',
                'description' => 'Ouverture/fermeture de caisse',
                'icon' => 'heroicon-o-banknotes',
                'category' => 'pos',
            ],

            // Ressources Humaines
            'hr' => [
                'label' => 'Module RH',
                'description' => 'Gestion des ressources humaines',
                'icon' => 'heroicon-o-users',
                'category' => 'hr',
            ],
            'employees' => [
                'label' => 'Employés',
                'description' => 'Gestion des employés',
                'icon' => 'heroicon-o-user-group',
                'category' => 'hr',
            ],
            'attendance' => [
                'label' => 'Présences',
                'description' => 'Suivi des présences',
                'icon' => 'heroicon-o-clock',
                'category' => 'hr',
            ],
            'schedules' => [
                'label' => 'Planning',
                'description' => 'Planification des horaires',
                'icon' => 'heroicon-o-calendar-days',
                'category' => 'hr',
            ],
            'leave_requests' => [
                'label' => 'Congés',
                'description' => 'Demandes de congés',
                'icon' => 'heroicon-o-sun',
                'category' => 'hr',
            ],
            'commissions' => [
                'label' => 'Commissions',
                'description' => 'Calcul des commissions',
                'icon' => 'heroicon-o-currency-euro',
                'category' => 'hr',
            ],

            // Comptabilité
            'accounting' => [
                'label' => 'Module Comptabilité',
                'description' => 'Gestion comptable complète',
                'icon' => 'heroicon-o-calculator',
                'category' => 'accounting',
            ],
            'bank_accounts' => [
                'label' => 'Comptes Bancaires',
                'description' => 'Gestion des comptes bancaires',
                'icon' => 'heroicon-o-building-library',
                'category' => 'accounting',
            ],
            'bank_transactions' => [
                'label' => 'Transactions Bancaires',
                'description' => 'Suivi des transactions',
                'icon' => 'heroicon-o-credit-card',
                'category' => 'accounting',
            ],
            'accounting_entries' => [
                'label' => 'Écritures Comptables',
                'description' => 'Saisie des écritures',
                'icon' => 'heroicon-o-document-plus',
                'category' => 'accounting',
            ],
            'accounting_reports' => [
                'label' => 'Rapports Comptables',
                'description' => 'Bilans et rapports',
                'icon' => 'heroicon-o-chart-bar',
                'category' => 'accounting',
            ],

            // Intégrations & Système
            'activity_logs' => [
                'label' => 'Journaux d\'activité',
                'description' => 'Historique des actions utilisateurs',
                'icon' => 'heroicon-o-document-magnifying-glass',
                'category' => 'integrations',
            ],
            'invitations' => [
                'label' => 'Invitations',
                'description' => 'Gestion des invitations utilisateurs',
                'icon' => 'heroicon-o-envelope',
                'category' => 'integrations',
            ],

            // CRM
            'customers' => [
                'label' => 'Clients',
                'description' => 'Gestion des clients',
                'icon' => 'heroicon-o-user-circle',
                'category' => 'crm',
            ],
            
            // Intégrations fiscales (Bénin)
            'emcef' => [
                'label' => 'e-MCeF (Bénin)',
                'description' => 'Certification électronique des factures - DGI Bénin',
                'icon' => 'heroicon-o-shield-check',
                'category' => 'integrations',
            ],
        ];
    }

    /**
     * Catégories de fonctionnalités
     */
    public static function getFeatureCategories(): array
    {
        return [
            'core' => [
                'label' => 'Ventes & Documents',
                'icon' => 'heroicon-o-shopping-cart',
                'color' => 'primary',
            ],
            'stock' => [
                'label' => 'Stocks & Achats',
                'icon' => 'heroicon-o-cube',
                'color' => 'success',
            ],
            'pos' => [
                'label' => 'Point de Vente',
                'icon' => 'heroicon-o-computer-desktop',
                'color' => 'warning',
            ],
            'hr' => [
                'label' => 'Ressources Humaines',
                'icon' => 'heroicon-o-users',
                'color' => 'info',
            ],
            'accounting' => [
                'label' => 'Comptabilité',
                'icon' => 'heroicon-o-calculator',
                'color' => 'danger',
            ],
            'integrations' => [
                'label' => 'Intégrations & Système',
                'icon' => 'heroicon-o-link',
                'color' => 'gray',
            ],
            'crm' => [
                'label' => 'CRM',
                'icon' => 'heroicon-o-user-circle',
                'color' => 'primary',
            ],
        ];
    }

    public function mount(): void
    {
        $this->loadGlobalFeatures();
    }

    protected function loadGlobalFeatures(): void
    {
        $globalSettings = Cache::get('global_features_settings', []);
        
        foreach (self::getAvailableFeatures() as $key => $feature) {
            $this->globalFeatures[$key] = $globalSettings[$key] ?? true;
        }
    }

    protected function loadCompanyFeatures(): void
    {
        if (!$this->selectedCompanyId) {
            return;
        }

        $company = Company::find($this->selectedCompanyId);
        if (!$company) {
            return;
        }

        $settings = $company->settings ?? [];
        $modules = $settings['modules'] ?? [];

        foreach (self::getAvailableFeatures() as $key => $feature) {
            $this->companyFeatures[$key] = $modules[$key] ?? true;
        }
    }

    public function updatedSelectedCompanyId(): void
    {
        $this->loadCompanyFeatures();
    }

    public function saveGlobalFeatures(): void
    {
        Cache::put('global_features_settings', $this->globalFeatures, now()->addYears(10));

        Notification::make()
            ->title('Configuration globale sauvegardée')
            ->success()
            ->send();
    }

    public function saveCompanyFeatures(): void
    {
        if (!$this->selectedCompanyId) {
            Notification::make()
                ->title('Veuillez sélectionner une entreprise')
                ->warning()
                ->send();
            return;
        }

        $company = Company::find($this->selectedCompanyId);
        if (!$company) {
            return;
        }

        $settings = $company->settings ?? [];
        $settings['modules'] = $this->companyFeatures;
        $company->settings = $settings;
        $company->save();

        // Invalider le cache
        $company->clearCache();

        Notification::make()
            ->title('Configuration de l\'entreprise sauvegardée')
            ->body("Les fonctionnalités de {$company->name} ont été mises à jour.")
            ->success()
            ->send();
    }

    public function enableAllGlobal(): void
    {
        foreach (self::getAvailableFeatures() as $key => $feature) {
            $this->globalFeatures[$key] = true;
        }
    }

    public function disableAllGlobal(): void
    {
        foreach (self::getAvailableFeatures() as $key => $feature) {
            $this->globalFeatures[$key] = false;
        }
    }

    public function enableAllCompany(): void
    {
        foreach (self::getAvailableFeatures() as $key => $feature) {
            $this->companyFeatures[$key] = true;
        }
    }

    public function disableAllCompany(): void
    {
        foreach (self::getAvailableFeatures() as $key => $feature) {
            $this->companyFeatures[$key] = false;
        }
    }

    public function applyGlobalToCompany(): void
    {
        if (!$this->selectedCompanyId) {
            Notification::make()
                ->title('Veuillez sélectionner une entreprise')
                ->warning()
                ->send();
            return;
        }

        $this->companyFeatures = $this->globalFeatures;

        Notification::make()
            ->title('Configuration globale appliquée')
            ->info()
            ->send();
    }

    public function applyGlobalToAllCompanies(): void
    {
        $companies = Company::all();
        
        foreach ($companies as $company) {
            $settings = $company->settings ?? [];
            $settings['modules'] = $this->globalFeatures;
            $company->settings = $settings;
            $company->save();
            $company->clearCache();
        }

        Notification::make()
            ->title('Configuration appliquée à toutes les entreprises')
            ->body($companies->count() . ' entreprises ont été mises à jour.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'features' => self::getAvailableFeatures(),
            'categories' => self::getFeatureCategories(),
            'companies' => Company::orderBy('name')->pluck('name', 'id'),
            'globalFeatures' => $this->globalFeatures,
            'companyFeatures' => $this->companyFeatures,
            'selectedCompanyId' => $this->selectedCompanyId,
        ];
    }
}
