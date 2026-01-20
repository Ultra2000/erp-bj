<?php

namespace App\Filament\Superadmin\Widgets;

use App\Filament\Superadmin\Pages\FeaturesManagement;
use App\Models\Company;
use App\Models\User;
use App\Models\Sale;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $globalFeatures = Cache::get('global_features_settings', []);
        $totalFeatures = count(FeaturesManagement::getAvailableFeatures());
        $enabledFeatures = collect($globalFeatures)->filter()->count();
        
        // Si aucune config globale n'existe, tout est activé par défaut
        if (empty($globalFeatures)) {
            $enabledFeatures = $totalFeatures;
        }

        $activeCompanies = Company::where('is_active', true)->count();
        $totalCompanies = Company::count();

        // Stats e-MCeF
        $emcefEnabledCompanies = Company::where('emcef_enabled', true)->count();
        $emcefProductionCompanies = Company::where('emcef_enabled', true)
            ->where('emcef_sandbox', false)
            ->count();
        $totalCertifiedInvoices = Sale::where('emcef_status', 'certified')->count();

        return [
            Stat::make('Entreprises', $activeCompanies . ' / ' . $totalCompanies)
                ->description('Actives / Total')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, $activeCompanies]),
            
            Stat::make('Utilisateurs', User::count())
                ->description(User::where('is_super_admin', true)->count() . ' super admins')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('e-MCeF Activé', $emcefEnabledCompanies . ' / ' . $totalCompanies)
                ->description($emcefProductionCompanies . ' en production')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($emcefEnabledCompanies > 0 ? 'success' : 'gray'),

            Stat::make('Factures Certifiées', number_format($totalCertifiedInvoices))
                ->description('Total certifiées DGI')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),

            Stat::make('Fonctionnalités', $enabledFeatures . ' / ' . $totalFeatures)
                ->description('Actives globalement')
                ->descriptionIcon('heroicon-m-puzzle-piece')
                ->color($enabledFeatures === $totalFeatures ? 'success' : 'warning')
                ->url(route('filament.superadmin.pages.features-management')),

            Stat::make('Super Admins', User::where('is_super_admin', true)->count())
                ->description('Accès système complet')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('danger'),
        ];
    }
}